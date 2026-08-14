<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const BACKUP_TABLE = 'rfid_scan_time_august_2026_backups';

    private const STARTING_AT = '2026-08-01 00:00:00';

    public function up(): void
    {
        if (! Schema::hasTable(self::BACKUP_TABLE)) {
            Schema::create(self::BACKUP_TABLE, function (Blueprint $table) {
                $table->unsignedBigInteger('transaction_id')->primary();
                $table->timestamp('scanned_at');
            });
        }

        DB::table('rfid_transactions')
            ->select(['id', 'scanned_at'])
            ->whereNotNull('scanned_at')
            ->where('scanned_at', '>=', self::STARTING_AT)
            ->orderBy('id')
            ->chunkById(500, function ($transactions): void {
                DB::table(self::BACKUP_TABLE)->insertOrIgnore(
                    $transactions->map(fn ($transaction) => [
                        'transaction_id' => $transaction->id,
                        'scanned_at' => $transaction->scanned_at,
                    ])->all()
                );
            });

        match (DB::getDriverName()) {
            'pgsql' => DB::statement(<<<'SQL'
                UPDATE rfid_transactions AS rt
                SET scanned_at = backup.scanned_at + INTERVAL '8 hours'
                FROM rfid_scan_time_august_2026_backups AS backup
                WHERE rt.id = backup.transaction_id
            SQL),
            'sqlite' => DB::statement(<<<'SQL'
                UPDATE rfid_transactions
                SET scanned_at = datetime((
                    SELECT backup.scanned_at
                    FROM rfid_scan_time_august_2026_backups AS backup
                    WHERE backup.transaction_id = rfid_transactions.id
                ), '+8 hours')
                WHERE id IN (SELECT transaction_id FROM rfid_scan_time_august_2026_backups)
            SQL),
            'mysql', 'mariadb' => DB::statement(<<<'SQL'
                UPDATE rfid_transactions AS rt
                INNER JOIN rfid_scan_time_august_2026_backups AS backup
                    ON backup.transaction_id = rt.id
                SET rt.scanned_at = DATE_ADD(backup.scanned_at, INTERVAL 8 HOUR)
            SQL),
            'sqlsrv' => DB::statement(<<<'SQL'
                UPDATE rt
                SET scanned_at = DATEADD(hour, 8, backup.scanned_at)
                FROM rfid_transactions AS rt
                INNER JOIN rfid_scan_time_august_2026_backups AS backup
                    ON backup.transaction_id = rt.id
            SQL),
            default => throw new \RuntimeException('Unsupported database driver for RFID scan-time correction.'),
        };
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::BACKUP_TABLE)) {
            return;
        }

        match (DB::getDriverName()) {
            'pgsql' => DB::statement(<<<'SQL'
                UPDATE rfid_transactions AS rt
                SET scanned_at = backup.scanned_at
                FROM rfid_scan_time_august_2026_backups AS backup
                WHERE rt.id = backup.transaction_id
            SQL),
            'sqlite' => DB::statement(<<<'SQL'
                UPDATE rfid_transactions
                SET scanned_at = (
                    SELECT backup.scanned_at
                    FROM rfid_scan_time_august_2026_backups AS backup
                    WHERE backup.transaction_id = rfid_transactions.id
                )
                WHERE id IN (SELECT transaction_id FROM rfid_scan_time_august_2026_backups)
            SQL),
            'mysql', 'mariadb' => DB::statement(<<<'SQL'
                UPDATE rfid_transactions AS rt
                INNER JOIN rfid_scan_time_august_2026_backups AS backup
                    ON backup.transaction_id = rt.id
                SET rt.scanned_at = backup.scanned_at
            SQL),
            'sqlsrv' => DB::statement(<<<'SQL'
                UPDATE rt
                SET scanned_at = backup.scanned_at
                FROM rfid_transactions AS rt
                INNER JOIN rfid_scan_time_august_2026_backups AS backup
                    ON backup.transaction_id = rt.id
            SQL),
            default => throw new \RuntimeException('Unsupported database driver for RFID scan-time restoration.'),
        };

        // Keep the original values permanently for auditing and recovery.
    }
};
