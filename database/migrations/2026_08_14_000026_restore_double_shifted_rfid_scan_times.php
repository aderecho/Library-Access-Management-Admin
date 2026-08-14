<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const AUGUST_BACKUP_TABLE = 'rfid_scan_time_august_2026_backups';

    private const ORIGINAL_CONVERSION_BACKUP_TABLE = 'rfid_transaction_scan_time_backups';

    private const REPAIR_BACKUP_TABLE = 'rfid_scan_time_double_shift_repair_backups';

    private const INCORRECT_NEXT_DAY_START = '2026-08-15 00:00:00';

    public function up(): void
    {
        if (! Schema::hasTable(self::AUGUST_BACKUP_TABLE)) {
            throw new RuntimeException('The August RFID scan-time backup table is required before repairing double-shifted values.');
        }

        if (! Schema::hasTable(self::REPAIR_BACKUP_TABLE)) {
            Schema::create(self::REPAIR_BACKUP_TABLE, function (Blueprint $table) {
                $table->unsignedBigInteger('transaction_id')->primary();
                $table->timestamp('scanned_at');
            });
        }

        DB::table('rfid_transactions as rt')
            ->join(self::AUGUST_BACKUP_TABLE.' as august_backup', 'august_backup.transaction_id', '=', 'rt.id')
            ->select(['rt.id', 'rt.scanned_at'])
            ->where(function ($query): void {
                $query->where('rt.scanned_at', '>=', self::INCORRECT_NEXT_DAY_START);

                if (Schema::hasTable(self::ORIGINAL_CONVERSION_BACKUP_TABLE)) {
                    $query->orWhereExists(function ($alreadyConverted): void {
                        $alreadyConverted->selectRaw('1')
                            ->from(self::ORIGINAL_CONVERSION_BACKUP_TABLE.' as original_backup')
                            ->whereColumn('original_backup.transaction_id', 'rt.id');
                    });
                }
            })
            ->orderBy('rt.id')
            ->chunkById(500, function ($transactions): void {
                DB::table(self::REPAIR_BACKUP_TABLE)->insertOrIgnore(
                    $transactions->map(fn ($transaction) => [
                        'transaction_id' => $transaction->id,
                        'scanned_at' => $transaction->scanned_at,
                    ])->all()
                );
            }, 'rt.id', 'id');

        match (DB::getDriverName()) {
            'pgsql' => DB::statement(<<<'SQL'
                UPDATE rfid_transactions AS rt
                SET scanned_at = original.scanned_at
                FROM rfid_scan_time_august_2026_backups AS original
                INNER JOIN rfid_scan_time_double_shift_repair_backups AS repair
                    ON repair.transaction_id = original.transaction_id
                WHERE rt.id = original.transaction_id
            SQL),
            'sqlite' => DB::statement(<<<'SQL'
                UPDATE rfid_transactions
                SET scanned_at = (
                    SELECT original.scanned_at
                    FROM rfid_scan_time_august_2026_backups AS original
                    WHERE original.transaction_id = rfid_transactions.id
                )
                WHERE id IN (SELECT transaction_id FROM rfid_scan_time_double_shift_repair_backups)
            SQL),
            'mysql', 'mariadb' => DB::statement(<<<'SQL'
                UPDATE rfid_transactions AS rt
                INNER JOIN rfid_scan_time_august_2026_backups AS original
                    ON original.transaction_id = rt.id
                INNER JOIN rfid_scan_time_double_shift_repair_backups AS repair
                    ON repair.transaction_id = rt.id
                SET rt.scanned_at = original.scanned_at
            SQL),
            'sqlsrv' => DB::statement(<<<'SQL'
                UPDATE rt
                SET scanned_at = original.scanned_at
                FROM rfid_transactions AS rt
                INNER JOIN rfid_scan_time_august_2026_backups AS original
                    ON original.transaction_id = rt.id
                INNER JOIN rfid_scan_time_double_shift_repair_backups AS repair
                    ON repair.transaction_id = rt.id
            SQL),
            default => throw new RuntimeException('Unsupported database driver for RFID scan-time repair.'),
        };
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::REPAIR_BACKUP_TABLE)) {
            return;
        }

        match (DB::getDriverName()) {
            'pgsql' => DB::statement(<<<'SQL'
                UPDATE rfid_transactions AS rt
                SET scanned_at = repair.scanned_at
                FROM rfid_scan_time_double_shift_repair_backups AS repair
                WHERE rt.id = repair.transaction_id
            SQL),
            'sqlite' => DB::statement(<<<'SQL'
                UPDATE rfid_transactions
                SET scanned_at = (
                    SELECT repair.scanned_at
                    FROM rfid_scan_time_double_shift_repair_backups AS repair
                    WHERE repair.transaction_id = rfid_transactions.id
                )
                WHERE id IN (SELECT transaction_id FROM rfid_scan_time_double_shift_repair_backups)
            SQL),
            'mysql', 'mariadb' => DB::statement(<<<'SQL'
                UPDATE rfid_transactions AS rt
                INNER JOIN rfid_scan_time_double_shift_repair_backups AS repair
                    ON repair.transaction_id = rt.id
                SET rt.scanned_at = repair.scanned_at
            SQL),
            'sqlsrv' => DB::statement(<<<'SQL'
                UPDATE rt
                SET scanned_at = repair.scanned_at
                FROM rfid_transactions AS rt
                INNER JOIN rfid_scan_time_double_shift_repair_backups AS repair
                    ON repair.transaction_id = rt.id
            SQL),
            default => throw new RuntimeException('Unsupported database driver for RFID scan-time repair rollback.'),
        };

        // Keep both the erroneous values and the original values permanently.
    }
};
