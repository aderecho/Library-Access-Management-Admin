<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('campus_id')->unique();
            $table->string('rfid_code')->unique();
            $table->string('name');
            $table->string('program')->nullable();
            $table->string('college')->nullable();
            $table->string('year_level')->nullable();
            $table->string('status')->default('Active Student');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
