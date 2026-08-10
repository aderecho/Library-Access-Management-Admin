<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PhilippineTimestampMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_utc_scan_times_are_converted_and_rollback_preserves_all_backup_data(): void
    {
        $migration = require database_path('migrations/2026_08_10_000024_convert_rfid_transaction_scanned_at_to_manila.php');

        // RefreshDatabase already applied the migration before this test.
        $migration->down();

        $transactionId = DB::table('rfid_transactions')->insertGetId([
            'rfid_code' => 'TIMEZONE-ENTRY-LOG',
            'cardholder_name' => 'Timezone Tester',
            'cardholder_type' => 'student',
            'transaction_type' => 'time_in',
            'status' => 'valid',
            'message' => 'Entry recorded.',
            'scanned_at' => '2026-08-10 08:43:03',
            'created_at' => '2026-08-10 08:43:04',
            'updated_at' => '2026-08-10 08:43:05',
        ]);

        $migration->up();

        $entryLog = DB::table('rfid_transactions')->where('id', $transactionId)->first();
        $backup = DB::table('rfid_transaction_scan_time_backups')
            ->where('transaction_id', $transactionId)
            ->first();

        $this->assertTimestampEquals('2026-08-10 16:43:03', $entryLog->scanned_at);
        $this->assertTimestampEquals('2026-08-10 08:43:04', $entryLog->created_at);
        $this->assertTimestampEquals('2026-08-10 08:43:05', $entryLog->updated_at);
        $this->assertTimestampEquals('2026-08-10 08:43:03', $backup->scanned_at);

        $migration->down();

        $entryLog = DB::table('rfid_transactions')->where('id', $transactionId)->first();
        $backup = DB::table('rfid_transaction_scan_time_backups')
            ->where('transaction_id', $transactionId)
            ->first();

        $this->assertTimestampEquals('2026-08-10 08:43:03', $entryLog->scanned_at);
        $this->assertTrue(Schema::hasTable('rfid_transaction_scan_time_backups'));
        $this->assertTimestampEquals('2026-08-10 08:43:03', $backup->scanned_at);
    }

    private function assertTimestampEquals(string $expected, mixed $actual): void
    {
        $this->assertStringStartsWith($expected, (string) $actual);
    }
}
