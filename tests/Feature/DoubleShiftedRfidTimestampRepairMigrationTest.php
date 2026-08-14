<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DoubleShiftedRfidTimestampRepairMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_restores_only_double_shifted_values_and_keeps_correct_conversions(): void
    {
        $migration = require database_path('migrations/2026_08_14_000026_restore_double_shifted_rfid_scan_times.php');

        // RefreshDatabase already applied this migration before the test.
        $migration->down();

        $previouslyConvertedId = $this->insertTransaction('2026-08-11 00:00:00', 'PREVIOUSLY-CONVERTED');
        $incorrectNextDayId = $this->insertTransaction('2026-08-15 00:54:22', 'INCORRECT-NEXT-DAY');
        $correctlyConvertedId = $this->insertTransaction('2026-08-14 16:38:45', 'CORRECT-CONVERSION');

        DB::table('rfid_scan_time_august_2026_backups')->insert([
            ['transaction_id' => $previouslyConvertedId, 'scanned_at' => '2026-08-10 16:00:00'],
            ['transaction_id' => $incorrectNextDayId, 'scanned_at' => '2026-08-14 16:54:22'],
            ['transaction_id' => $correctlyConvertedId, 'scanned_at' => '2026-08-14 08:38:45'],
        ]);

        DB::table('rfid_transaction_scan_time_backups')->insert([
            'transaction_id' => $previouslyConvertedId,
            'scanned_at' => '2026-08-10 08:00:00',
        ]);

        $migration->up();

        $this->assertTimestampEquals('2026-08-10 16:00:00', $this->scanTime($previouslyConvertedId));
        $this->assertTimestampEquals('2026-08-14 16:54:22', $this->scanTime($incorrectNextDayId));
        $this->assertTimestampEquals('2026-08-14 16:38:45', $this->scanTime($correctlyConvertedId));
        $this->assertSame(2, DB::table('rfid_scan_time_double_shift_repair_backups')->count());

        $migration->down();

        $this->assertTimestampEquals('2026-08-11 00:00:00', $this->scanTime($previouslyConvertedId));
        $this->assertTimestampEquals('2026-08-15 00:54:22', $this->scanTime($incorrectNextDayId));
        $this->assertTrue(Schema::hasTable('rfid_scan_time_double_shift_repair_backups'));
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

    private function scanTime(int $transactionId): mixed
    {
        return DB::table('rfid_transactions')->where('id', $transactionId)->value('scanned_at');
    }

    private function assertTimestampEquals(string $expected, mixed $actual): void
    {
        $this->assertStringStartsWith($expected, (string) $actual);
    }
}
