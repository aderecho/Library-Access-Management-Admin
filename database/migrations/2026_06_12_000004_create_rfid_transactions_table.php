<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfid_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->string('rfid_code')->index();
            $table->string('campus_id')->nullable()->index();
            $table->string('student_name');
            $table->string('transaction_type')->default('time_in')->index();
            $table->string('status')->index();
            $table->string('message')->nullable();
            $table->timestamp('scanned_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfid_transactions');
    }
};
