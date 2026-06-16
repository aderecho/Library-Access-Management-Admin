<?php

namespace Tests\Feature;

use App\Events\RfidScanRecorded;
use App\Models\Role;
use App\Models\ScannerToken;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class LiveScanUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_rfid_scan_broadcasts_live_update_after_recording_transaction(): void
    {
        Event::fake([RfidScanRecorded::class]);

        $student = Student::create([
            'campus_id' => '2026-0001',
            'rfid_code' => 'live-student-rfid',
            'name' => 'Live Student',
            'is_active' => true,
        ]);

        $scannerToken = 'upcebu_scanner_live_test';
        ScannerToken::create([
            'name' => 'Live Test Scanner',
            'token_hash' => hash('sha256', $scannerToken),
            'token_prefix' => substr($scannerToken, 0, 22),
            'is_active' => true,
        ]);

        $this->withHeader('X-Scanner-Token', $scannerToken)
            ->postJson(route('api.rfid.scan'), ['rfid_code' => $student->rfid_code])
            ->assertOk();

        Event::assertDispatched(RfidScanRecorded::class, function (RfidScanRecorded $event) use ($student) {
            return $event->transaction->student_id === $student->id
                && $event->transaction->exists;
        });
    }

    public function test_dashboard_transaction_and_report_pages_enable_websocket_updates(): void
    {
        $admin = $this->createAdmin(['dashboard.view', 'transactions.view', 'reports.view']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('data-live-scan-page', false);

        $this->actingAs($admin)
            ->get(route('admin.transactions.index'))
            ->assertOk()
            ->assertSee('data-live-scan-page', false);

        $this->actingAs($admin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('data-live-scan-page', false);
    }

    public function test_authorized_admin_can_join_the_live_scan_channel(): void
    {
        $this->useReverbBroadcaster();
        $authorizedAdmin = $this->createAdmin(['dashboard.view']);

        $this->actingAs($authorizedAdmin)
            ->postJson('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => 'private-admin.rfid-scans',
            ])
            ->assertOk();
    }

    public function test_report_viewer_can_join_the_live_scan_channel(): void
    {
        $this->useReverbBroadcaster();
        $reportViewer = $this->createAdmin(['reports.view']);

        $this->actingAs($reportViewer)
            ->postJson('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => 'private-admin.rfid-scans',
            ])
            ->assertOk();
    }

    public function test_unauthorized_admin_cannot_join_the_live_scan_channel(): void
    {
        $this->useReverbBroadcaster();
        $unauthorizedAdmin = $this->createAdmin(['users.view']);

        $this->actingAs($unauthorizedAdmin)
            ->postJson('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => 'private-admin.rfid-scans',
            ])
            ->assertForbidden();
    }

    private function useReverbBroadcaster(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
        ]);

        Broadcast::forgetDrivers();
        require base_path('routes/channels.php');
    }

    private function createAdmin(array $permissions): User
    {
        $role = Role::create([
            'name' => fake()->unique()->words(2, true),
            'slug' => fake()->unique()->slug(),
            'permissions' => $permissions,
        ]);

        return User::create([
            'name' => 'Admin User',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }
}
