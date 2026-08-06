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

    public function test_dashboard_metric_icons_keep_their_centering_layout(): void
    {
        $stylesheet = file_get_contents(public_path('css/admin.css'));

        $this->assertIsString($stylesheet);
        $this->assertStringContainsString('.metric .metric-icon{display:grid;', $stylesheet);
        $this->assertStringContainsString('.metric .metric-icon svg{display:block;width:100%;height:100%;overflow:visible;', $stylesheet);
        $this->assertStringContainsString('shape-rendering:geometricPrecision', $stylesheet);
        $this->assertStringContainsString('.metric .metric-icon svg>*{vector-effect:non-scaling-stroke}', $stylesheet);
    }

    public function test_dashboard_displays_program_college_and_year_level_charts(): void
    {
        $role = Role::create([
            'name' => 'Dashboard Viewer',
            'slug' => 'dashboard-viewer',
            'permissions' => ['dashboard.view'],
        ]);

        $admin = User::create([
            'branch_id' => $this->defaultBranch()->id,
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
            ->assertSee('data-dashboard-metric="today_scans"', false)
            ->assertSee('metric-icon', false)
            ->assertSee('class="chart-line total-line"', false)
            ->assertSee('class="chart-area total-area"', false)
            ->assertDontSee('<polyline', false)
            ->assertDontSee('pathLength=', false)
            ->assertSee('Program Distribution')
            ->assertSee('College Distribution')
            ->assertSee('Year Level Distribution')
            ->assertSee('class="distribution-overview"', false)
            ->assertSee('class="distribution-card-index"', false)
            ->assertSee('role="progressbar"', false)
            ->assertSee('BS Computer Science')
            ->assertSee('College of Science')
            ->assertSee('3rd Year');
    }

    public function test_dashboard_groups_content_into_accessible_tabs(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('role="tablist"', false)
            ->assertSee('data-dashboard-tab="overview"', false)
            ->assertSee('data-dashboard-tab="entry-analytics"', false)
            ->assertSee('data-dashboard-tab="students"', false)
            ->assertSee('data-dashboard-panel="overview"', false)
            ->assertSee('id="dashboard-entry-analytics-panel"', false)
            ->assertSee('id="dashboard-students-panel"', false)
            ->assertSee('class="access-summary-feature"', false)
            ->assertSee('class="recent-transaction-list"', false)
            ->assertSee('class="analytics-data-disclosure"', false)
            ->assertSee('data-monthly-chart-scroll', false)
            ->assertSee('View exact monthly values')
            ->assertSee('View exact branch values')
            ->assertSee('The five most recent access attempts');

        $script = file_get_contents(resource_path('js/dashboard-tabs.js'));
        $stylesheet = file_get_contents(public_path('css/admin.css'));

        $this->assertIsString($script);
        $this->assertIsString($stylesheet);
        $this->assertStringContainsString("event.key === 'ArrowRight'", $script);
        $this->assertStringContainsString("window.addEventListener('hashchange'", $script);
        $this->assertStringContainsString("activePanel.scrollIntoView", $script);
        $this->assertStringContainsString('positionMobileChart', $script);
        $this->assertStringContainsString('.dashboard-tab-panel[hidden]{display:none}', $stylesheet);
    }

    public function test_dashboard_cache_is_invalidated_after_a_new_transaction(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('<strong>0</strong>', false);

        RfidTransaction::create([
            'branch_id' => $this->defaultBranch()->id,
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
            'branch_id' => $this->defaultBranch()->id,
            'name' => 'Dashboard Admin',
            'email' => 'dashboard-admin@example.com',
            'password' => 'password123',
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    public function test_dashboard_compares_entries_per_authorized_branch(): void
    {
        $admin = $this->createAdmin();
        RfidTransaction::create([
            'branch_id' => $this->defaultBranch()->id,
            'cardholder_type' => 'student',
            'rfid_code' => 'branch-chart-rfid',
            'cardholder_name' => 'Branch Chart Student',
            'transaction_type' => 'time_in',
            'status' => 'valid',
            'scanned_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Entries per Library Branch')
            ->assertSee($this->defaultBranch()->name)
            ->assertSee('1 verified');
    }
}
