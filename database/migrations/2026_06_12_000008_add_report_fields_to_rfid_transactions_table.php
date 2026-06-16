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
            $table->string('program')->nullable()->after('cardholder_name');
            $table->string('college_department')->nullable()->after('program');
        });

        DB::table('rfid_transactions')
            ->whereNotNull('student_id')
            ->orderBy('id')
            ->chunkById(500, function ($transactions) {
                $students = DB::table('students')
                    ->whereIn('id', $transactions->pluck('student_id')->filter()->unique())
                    ->get()
                    ->keyBy('id');

                foreach ($transactions as $transaction) {
                    $student = $students->get($transaction->student_id);

                    DB::table('rfid_transactions')
                        ->where('id', $transaction->id)
                        ->update([
                            'program' => $student?->program,
                            'college_department' => $student?->college,
                        ]);
                }
            });

        DB::table('rfid_transactions')
            ->whereNotNull('employee_id')
            ->orderBy('id')
            ->chunkById(500, function ($transactions) {
                $employees = DB::table('employees')
                    ->whereIn('id', $transactions->pluck('employee_id')->filter()->unique())
                    ->get()
                    ->keyBy('id');

                foreach ($transactions as $transaction) {
                    $employee = $employees->get($transaction->employee_id);

                    DB::table('rfid_transactions')
                        ->where('id', $transaction->id)
                        ->update([
                            'program' => $employee?->position,
                            'college_department' => $employee?->office,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('rfid_transactions', function (Blueprint $table) {
            $table->dropColumn(['program', 'college_department']);
        });
    }
};
