<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AugustRfidTimestampCorrectionMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_eight_hours_from_august_1_through_august_14_and_preserves_original_values(): void
    {
        $migration = require database_path('migrations/2026_08_14_000025_add_eight_hours_to_rfid_scans_from_august_1.php');

        // RefreshDatabase already applied this migration before the test.
        $migration->down();

        $julyTransactionId = $this->insertTransaction('2026-07-31 23:59:59', 'JULY-SCAN');
        $augustTransactionId = $this->insertTransaction('2026-08-01 00:00:00', 'AUGUST-SCAN');
        $crossMidnightTransactionId = $this->insertTransaction('2026-08-14 16:43:59', 'MIDNIGHT-SCAN');
        $tomorrowTransactionId = $this->insertTransaction('2026-08-15 00:00:00', 'TOMORROW-SCAN');

        $migration->up();

        $this->assertTimestampEquals(
            '2026-07-31 23:59:59',
            DB::table('rfid_transactions')->where('id', $julyTransactionId)->value('scanned_at')
        );
        $this->assertTimestampEquals(
            '2026-08-01 08:00:00',
            DB::table('rfid_transactions')->where('id', $augustTransactionId)->value('scanned_at')
        );
        $this->assertTimestampEquals(
            '2026-08-15 00:43:59',
            DB::table('rfid_transactions')->where('id', $crossMidnightTransactionId)->value('scanned_at')
        );
        $this->assertTimestampEquals(
            '2026-08-15 00:00:00',
            DB::table('rfid_transactions')->where('id', $tomorrowTransactionId)->value('scanned_at')
        );

        $this->assertSame(2, DB::table('rfid_scan_time_august_2026_backups')->count());
        $this->assertTimestampEquals(
            '2026-08-14 16:43:59',
            DB::table('rfid_scan_time_august_2026_backups')
                ->where('transaction_id', $crossMidnightTransactionId)
                ->value('scanned_at')
        );

        $migration->down();

        $this->assertTimestampEquals(
            '2026-08-14 16:43:59',
            DB::table('rfid_transactions')->where('id', $crossMidnightTransactionId)->value('scanned_at')
        );
        $this->assertTrue(Schema::hasTable('rfid_scan_time_august_2026_backups'));
        $this->assertSame(2, DB::table('rfid_scan_time_august_2026_backups')->count());
    }

    private function insertTransaction(string $scannedAt, string $rfidCode): int
    {
        return DB::table('rfid_transactions')->insertGetId([
            'rfid_code' => $rfidCode,
            'cardholder_name' => 'Timezone Tester',
            'cardholder_type' => 'student',
            'transaction_type' => 'time_in',
            'status' => 'valid',
            'message' => 'Entry recorded.',
            'scanned_at' => $scannedAt,
            'created_at' => $scannedAt,
            'updated_at' => $scannedAt,
        ]);
    }

    private function assertTimestampEquals(string $expected, mixed $actual): void
    {
        $this->assertStringStartsWith($expected, (string) $actual);
    }
}
