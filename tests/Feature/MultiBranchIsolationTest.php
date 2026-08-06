<?php

namespace Tests\Feature;

use App\Events\RfidScanRecorded;
use App\Models\Branch;
use App\Models\RfidTransaction;
use App\Models\Role;
use App\Models\ScannerToken;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MultiBranchIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_scanner_records_and_broadcasts_scan_only_for_its_branch(): void
    {
        Event::fake([RfidScanRecorded::class]);
        [$branchOne, $branchTwo] = $this->branches();
        $token = 'branch-one-scanner-token';
        ScannerToken::create(['branch_id' => $branchOne->id, 'name' => 'Branch 1 Door', 'token_hash' => hash('sha256', $token), 'token_prefix' => 'branch-one', 'is_active' => true]);
        $student = Student::create(['campus_id' => '2026-1000', 'rfid_code' => 'rfid-1000', 'name' => 'Branch Student', 'is_active' => true]);

        $this->withHeader('X-Scanner-Token', $token)->postJson(route('api.rfid.scan'), ['rfid_code' => $student->rfid_code])->assertOk()->assertJsonPath('branch.id', $branchOne->id);

        $this->assertDatabaseHas('rfid_transactions', ['branch_id' => $branchOne->id, 'student_id' => $student->id]);
        $this->assertDatabaseMissing('rfid_transactions', ['branch_id' => $branchTwo->id, 'student_id' => $student->id]);
        Event::assertDispatched(RfidScanRecorded::class, fn ($event) => $event->transaction->branch_id === $branchOne->id);
    }

    public function test_branch_monitor_only_sees_transactions_from_assigned_branch(): void
    {
        [$branchOne, $branchTwo] = $this->branches();
        $monitor = $this->monitor($branchOne);
        $this->transaction($branchOne, 'Visible Student');
        $this->transaction($branchTwo, 'Hidden Student');

        $this->actingAs($monitor)->get(route('admin.entry-monitor'))->assertOk()->assertSee('Visible Student')->assertDontSee('Hidden Student')->assertSee('data-branch-id="'.$branchOne->id.'"', false);
    }

    public function test_branch_monitor_cannot_authorize_another_branch_channel(): void
    {
        [$branchOne, $branchTwo] = $this->branches();
        $this->useReverbBroadcaster();
        $monitor = $this->monitor($branchOne);

        $this->actingAs($monitor)->postJson('/broadcasting/auth', ['socket_id' => '1.2', 'channel_name' => 'private-branches.'.$branchOne->id.'.rfid-scans'])->assertOk();
        $this->actingAs($monitor)->postJson('/broadcasting/auth', ['socket_id' => '1.2', 'channel_name' => 'private-branches.'.$branchTwo->id.'.rfid-scans'])->assertForbidden();
    }

    public function test_transaction_log_shows_branch_entered_and_excludes_other_branches(): void
    {
        [$branchOne, $branchTwo] = $this->branches();
        $monitor = $this->monitor($branchOne);
        $this->transaction($branchOne, 'Visible Audit Student');
        $this->transaction($branchTwo, 'Hidden Audit Student');

        $this->actingAs($monitor)->get(route('admin.transactions.index'))
            ->assertOk()
            ->assertSee('Branch Entered')
            ->assertSee($branchOne->name)
            ->assertSee('Visible Audit Student')
            ->assertDontSee('Hidden Audit Student');
    }

    private function branches(): array
    {
        return [Branch::create(['name' => 'Branch 1', 'code' => 'B1']), Branch::create(['name' => 'Branch 2', 'code' => 'B2'])];
    }

    private function monitor(Branch $branch): User
    {
        $role = Role::create(['name' => 'Branch Monitor', 'slug' => 'branch-monitor-'.fake()->unique()->numberBetween(1, 9999), 'permissions' => ['entry-monitor.view', 'transactions.view']]);

        return User::factory()->create(['branch_id' => $branch->id, 'role_id' => $role->id, 'is_active' => true]);
    }

    private function transaction(Branch $branch, string $name): void
    {
        RfidTransaction::create(['branch_id' => $branch->id, 'cardholder_type' => 'student', 'rfid_code' => fake()->unique()->uuid(), 'cardholder_name' => $name, 'transaction_type' => 'time_in', 'status' => 'valid', 'message' => 'Entry recorded.', 'scanned_at' => now()]);
    }

    private function useReverbBroadcaster(): void
    {
        config(['broadcasting.default' => 'reverb', 'broadcasting.connections.reverb.key' => 'test-key', 'broadcasting.connections.reverb.secret' => 'test-secret', 'broadcasting.connections.reverb.app_id' => 'test-app']);
        Broadcast::forgetDrivers();
        require base_path('routes/channels.php');
    }
}
