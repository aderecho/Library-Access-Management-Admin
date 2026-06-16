<?php

namespace Tests\Feature;

use App\Models\RfidTransaction;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStudentChartsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_program_college_and_year_level_charts(): void
    {
        $role = Role::create([
            'name' => 'Dashboard Viewer',
            'slug' => 'dashboard-viewer',
            'permissions' => ['dashboard.view'],
        ]);

        $admin = User::create([
            'name' => 'Dashboard Viewer',
            'email' => 'dashboard@example.com',
            'password' => 'password123',
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        Student::create([
            'campus_id' => '2026-20001',
            'rfid_code' => 'dashboard-student-rfid',
            'name' => 'Dashboard Student',
            'program' => 'BS Computer Science',
            'college' => 'College of Science',
            'year_level' => '3rd Year',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Program Distribution')
            ->assertSee('College Distribution')
            ->assertSee('Year Level Distribution')
            ->assertSee('BS Computer Science')
            ->assertSee('College of Science')
            ->assertSee('3rd Year');
    }

    public function test_dashboard_cache_is_invalidated_after_a_new_transaction(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('<strong>0</strong>', false);

        RfidTransaction::create([
            'cardholder_type' => 'unknown',
            'rfid_code' => 'cache-invalidation-test',
            'cardholder_name' => 'Unregistered Card',
            'transaction_type' => 'time_in',
            'status' => 'invalid',
            'message' => 'Unregistered',
            'scanned_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('<strong>1</strong>', false);
    }

    private function createAdmin(): User
    {
        $role = Role::create([
            'name' => 'Dashboard Admin',
            'slug' => 'dashboard-admin',
            'permissions' => ['dashboard.view'],
        ]);

        return User::create([
            'name' => 'Dashboard Admin',
            'email' => 'dashboard-admin@example.com',
            'password' => 'password123',
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }
}
