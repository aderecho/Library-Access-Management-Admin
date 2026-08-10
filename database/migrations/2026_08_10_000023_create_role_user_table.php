<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('role_user')) {
            Schema::create('role_user', function (Blueprint $table) {
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('role_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->primary(['user_id', 'role_id']);
            });
        }

        DB::table('users')
            ->select(['id', 'role_id'])
            ->whereNotNull('role_id')
            ->orderBy('id')
            ->chunkById(500, function ($users): void {
                $now = now();

                DB::table('role_user')->insertOrIgnore(
                    $users->map(fn ($user) => [
                        'user_id' => $user->id,
                        'role_id' => $user->role_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            });
    }

    public function down(): void
    {
        // Intentionally preserve role_user and every role assignment.
    }
};
