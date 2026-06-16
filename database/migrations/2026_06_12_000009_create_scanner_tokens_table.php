<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scanner_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('device_id')->nullable()->unique();
            $table->string('token_hash', 64)->unique();
            $table->string('token_prefix', 24);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scanner_tokens');
    }
};
