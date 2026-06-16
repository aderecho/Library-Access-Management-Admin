<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfid_transactions', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->after('student_id')->constrained()->nullOnDelete();
            $table->string('cardholder_type')->default('student')->after('employee_id')->index();
            $table->renameColumn('student_name', 'cardholder_name');
        });
    }

    public function down(): void
    {
        Schema::table('rfid_transactions', function (Blueprint $table) {
            $table->renameColumn('cardholder_name', 'student_name');
            $table->dropIndex(['cardholder_type']);
            $table->dropColumn('cardholder_type');
            $table->dropConstrainedForeignId('employee_id');
        });
    }
};
