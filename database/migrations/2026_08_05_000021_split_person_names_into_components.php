<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = ['users', 'students', 'employees'];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('first_name')->default('')->after('name');
                $table->string('middle_name')->nullable()->after('first_name');
                $table->string('last_name')->default('')->after('middle_name');
                $table->string('suffix', 30)->nullable()->after('last_name');
            });

            DB::table($tableName)
                ->select(['id', 'name'])
                ->orderBy('id')
                ->chunkById(200, function ($rows) use ($tableName) {
                    foreach ($rows as $row) {
                        DB::table($tableName)
                            ->where('id', $row->id)
                            ->update($this->splitName($row->name));
                    }
                });

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('name')->default('')->after('id');
            });

            DB::table($tableName)
                ->select(['id', 'first_name', 'middle_name', 'last_name', 'suffix'])
                ->orderBy('id')
                ->chunkById(200, function ($rows) use ($tableName) {
                    foreach ($rows as $row) {
                        $name = collect([$row->first_name, $row->middle_name, $row->last_name, $row->suffix])
                            ->filter(fn ($part) => filled($part))
                            ->map(fn ($part) => trim((string) $part))
                            ->implode(' ');

                        DB::table($tableName)->where('id', $row->id)->update(['name' => $name]);
                    }
                });

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['first_name', 'middle_name', 'last_name', 'suffix']);
            });
        }
    }

    private function splitName(?string $name): array
    {
        $normalized = trim((string) preg_replace('/\s+/', ' ', (string) $name));
        $parts = $normalized === '' ? [] : explode(' ', $normalized);
        $suffix = null;

        if ($parts !== [] && preg_match('/^(Jr\.?|Sr\.?|II|III|IV|V)$/i', (string) end($parts))) {
            $suffix = array_pop($parts);
        }

        $firstName = array_shift($parts) ?? '';
        $lastNameParts = [];

        if ($parts !== []) {
            array_unshift($lastNameParts, array_pop($parts));
            $surnamePrefixes = ['da', 'das', 'de', 'del', 'dela', 'di', 'dos', 'du', 'la', 'le', 'san', 'santa', 'st.', 'van', 'von'];

            while ($parts !== [] && in_array(strtolower((string) end($parts)), $surnamePrefixes, true)) {
                array_unshift($lastNameParts, array_pop($parts));
            }
        }

        return [
            'first_name' => $firstName,
            'middle_name' => $parts === [] ? null : implode(' ', $parts),
            'last_name' => implode(' ', $lastNameParts),
            'suffix' => $suffix,
        ];
    }
};
