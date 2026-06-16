<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfid_transaction_cdc_outbox', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->string('operation', 10);
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('created_at');
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION capture_rfid_transaction_change()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO rfid_transaction_cdc_outbox (transaction_id, operation, payload, created_at)
                VALUES (
                    COALESCE(NEW.id, OLD.id),
                    TG_OP,
                    CASE WHEN TG_OP = 'DELETE' THEN NULL ELSE row_to_json(NEW)::jsonb END,
                    CURRENT_TIMESTAMP
                );

                RETURN COALESCE(NEW, OLD);
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER rfid_transactions_cdc_trigger
            AFTER INSERT OR UPDATE OR DELETE ON rfid_transactions
            FOR EACH ROW EXECUTE FUNCTION capture_rfid_transaction_change();
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS rfid_transactions_cdc_trigger ON rfid_transactions');
            DB::unprepared('DROP FUNCTION IF EXISTS capture_rfid_transaction_change()');
        }

        Schema::dropIfExists('rfid_transaction_cdc_outbox');
    }
};
