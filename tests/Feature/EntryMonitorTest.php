<?php

namespace Tests\Feature;

use App\Models\RfidTransaction;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntryMonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_guard_can_monitor_latest_entry_details(): void
    {
        $role = Role::create([
            'name' => 'Security Guard',
            'slug' => 'security-guard',
            'permissions' => ['transactions.view'],
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        RfidTransaction::create([
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
            ->assertSee('data-activity-row', false)
            ->assertSee('data-activity-dialog', false)
            ->assertSee('College / Department');
    }
}
