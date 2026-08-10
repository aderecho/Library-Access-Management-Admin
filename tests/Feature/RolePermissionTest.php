<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_can_combine_entry_monitor_and_report_page_access(): void
    {
        $superAdminRole = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'permissions' => [],
        ]);
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'combined-role-admin@example.com',
            'password' => 'password123',
            'role_id' => $superAdminRole->id,
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('admin.roles.create'))
            ->assertOk()
            ->assertSee('Page and action access')
            ->assertSee('value="entry-monitor.view"', false)
            ->assertSee('value="reports.view"', false);

        $this->actingAs($superAdmin)->post(route('admin.roles.store'), [
            'name' => 'Entry and Reports',
            'description' => 'Monitors live entries and reviews branch reports.',
            'permissions' => ['entry-monitor.view', 'reports.view'],
        ])->assertRedirect(route('admin.roles.index'));

        $role = Role::where('slug', 'entry-and-reports')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            ['entry-monitor.view', 'reports.view'],
            $role->permissions
        );

        $user = User::create([
            'branch_id' => $this->defaultBranch()->id,
            'name' => 'Entry and Reports User',
            'email' => 'entry-reports@example.com',
            'password' => 'password123',
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.entry-monitor'));

        $this->actingAs($user)
            ->get(route('admin.entry-monitor'))
            ->assertOk()
            ->assertSee($this->defaultBranch()->name)
            ->assertSee(route('admin.entry-monitor'), false)
            ->assertSee(route('admin.reports.index'), false)
            ->assertDontSee(route('admin.transactions.index'), false);

        $this->actingAs($user)->get(route('admin.reports.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.transactions.index'))->assertForbidden();
    }

    public function test_user_can_only_access_routes_allowed_by_role_permissions(): void
    {
        $role = Role::create([
            'name' => 'Report Viewer',
            'slug' => 'report-viewer',
            'permissions' => ['reports.view'],
        ]);

        $user = User::create([
            'branch_id' => $this->defaultBranch()->id,
            'name' => 'Report Viewer',
            'email' => 'viewer@example.com',
            'password' => 'password123',
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)->get('/admin/reports')->assertOk();
        $this->actingAs($user)->get('/admin')->assertRedirect(route('admin.reports.index'));
        $this->actingAs($user)->get('/admin/transactions')->assertForbidden();
    }

    public function test_super_admin_has_all_permissions_without_explicit_permission_list(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'permissions' => [],
        ]);

        $user = User::create([
            'branch_id' => $this->defaultBranch()->id,
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => 'password123',
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->assertTrue($user->hasPermission('roles.update'));
    }

    public function test_report_viewer_is_redirected_to_first_permitted_page_after_sso_login(): void
    {
        $role = Role::create([
            'name' => 'Report Viewer',
            'slug' => 'report-viewer',
            'permissions' => ['reports.view'],
        ]);

        $user = User::create([
            'name' => 'Report Viewer',
            'email' => 'reports@example.com',
            'password' => 'password123',
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        Socialite::fake('google', (new SocialiteUser)->map([
            'id' => 'google-report-viewer',
            'name' => $user->name,
            'email' => $user->email,
        ]));

        $this->get(route('login.google.callback'))
            ->assertRedirect(route('admin.reports.index'));
    }

    public function test_entry_monitor_is_redirected_to_monitor_after_sso_even_with_admin_intended_url(): void
    {
        $role = Role::where('slug', 'entry-monitor')->firstOrFail();

        $user = User::create([
            'branch_id' => $this->defaultBranch()->id,
            'name' => 'Entry Monitor',
            'email' => 'monitor@example.com',
            'password' => 'password123',
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        Socialite::fake('google', (new SocialiteUser)->map([
            'id' => 'google-entry-monitor',
            'name' => $user->name,
            'email' => $user->email,
        ]));

        $this->withSession(['url.intended' => route('admin.dashboard')])
            ->get(route('login.google.callback'))
            ->assertRedirect(route('admin.entry-monitor'));
    }
}
