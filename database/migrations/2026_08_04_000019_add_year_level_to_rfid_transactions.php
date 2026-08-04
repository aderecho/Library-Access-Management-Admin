<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfid_transactions', function (Blueprint $table) {
            $table->string('year_level')->nullable()->after('college_department');
        });

        DB::table('rfid_transactions')
            ->whereNotNull('student_id')
            ->whereNull('year_level')
            ->orderBy('id')
            ->chunkById(500, function ($transactions) {
                $yearLevels = DB::table('students')
                    ->whereIn('id', $transactions->pluck('student_id')->filter()->unique())
                    ->pluck('year_level', 'id');

                foreach ($transactions as $transaction) {
                    DB::table('rfid_transactions')
                        ->where('id', $transaction->id)
                        ->update(['year_level' => $yearLevels[$transaction->student_id] ?? null]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('rfid_transactions', function (Blueprint $table) {
            $table->dropColumn('year_level');
        });
    }
};
