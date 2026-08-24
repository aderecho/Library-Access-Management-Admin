<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfid_change_logs', function (Blueprint $table) {
            $table->id();
            $table->string('cardholder_type', 20);
            $table->unsignedBigInteger('cardholder_id');
            $table->string('cardholder_identifier');
            $table->string('cardholder_name');
            $table->string('old_rfid_code');
            $table->string('new_rfid_code');
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['cardholder_type', 'cardholder_id', 'created_at']);
        });

        DB::table('roles')->insertOrIgnore([
            'name' => 'RFID Directory',
            'slug' => 'rfid-directory',
            'description' => 'Staff who view and update student and employee RFID records.',
            'permissions' => json_encode(['rfid-directory.view', 'rfid-directory.update']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Preserve RFID change history, the role, permissions, and user assignments on rollback.
    }
};
