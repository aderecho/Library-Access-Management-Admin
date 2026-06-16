<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfid_transactions', function (Blueprint $table) {
            $table->index(['scanned_at', 'status'], 'rfid_transactions_scanned_status_index');
            $table->index(['campus_id', 'scanned_at'], 'rfid_transactions_campus_scanned_index');
        });
    }

    public function down(): void
    {
        Schema::table('rfid_transactions', function (Blueprint $table) {
            $table->dropIndex('rfid_transactions_scanned_status_index');
            $table->dropIndex('rfid_transactions_campus_scanned_index');
        });
    }
};
