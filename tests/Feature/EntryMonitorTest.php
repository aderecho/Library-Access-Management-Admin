<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\RfidTransaction;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntryMonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_branch_selector_uses_the_compact_monitor_toolbar(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
        ]);
        $user = User::factory()->create([
            'branch_id' => null,
            'role_id' => $role->id,
            'is_active' => true,
        ]);
        $secondBranch = Branch::create([
            'name' => 'SRP Campus',
            'code' => 'SRP',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('admin.entry-monitor', ['branch_id' => $secondBranch->id]))
            ->assertOk()
            ->assertSee('class="monitor-control-bar"', false)
            ->assertSee('class="monitor-branch-form"', false)
            ->assertSee('id="monitor-branch-select"', false)
            ->assertSee('SRP Campus')
            ->assertDontSee('class="panel form-grid"', false);
    }

    public function test_authorized_guard_can_monitor_latest_entry_details(): void
    {
        $role = Role::create([
            'name' => 'Security Guard',
            'slug' => 'security-guard',
            'permissions' => ['entry-monitor.view'],
        ]);

        $user = User::factory()->create([
            'branch_id' => $this->defaultBranch()->id,
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        RfidTransaction::create([
            'branch_id' => $this->defaultBranch()->id,
            'cardholder_type' => 'student',
            'rfid_code' => '04 A3 6F 21',
            'campus_id' => '2023-12345',
            'cardholder_name' => 'Maria Isabel Santos',
            'program' => 'BS Computer Science',
            'transaction_type' => 'time_in',
            'status' => 'valid',
            'message' => 'Entry recorded successfully.',
            'scanned_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.entry-monitor'))
            ->assertOk()
            ->assertSee('ENTRY VERIFIED')
            ->assertSee('Maria Isabel Santos')
            ->assertSee('2023-12345')
            ->assertSee('04 A3 6F 21')
            ->assertSee('BS Computer Science')
            ->assertSee('Library Branch')
            ->assertSee($this->defaultBranch()->name)
            ->assertSee('class="monitor-control-bar"', false)
            ->assertSee('class="monitor-branch-name"', false)
            ->assertSee('data-activity-row', false)
            ->assertSee('data-activity-dialog', false)
            ->assertSee('College / Department');
    }
}
