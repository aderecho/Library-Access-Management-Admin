<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_only_access_routes_allowed_by_role_permissions(): void
    {
        $role = Role::create([
            'name' => 'Report Viewer',
            'slug' => 'report-viewer',
            'permissions' => ['reports.view'],
        ]);

        $user = User::create([
            'name' => 'Report Viewer',
            'email' => 'viewer@example.com',
            'password' => 'password123',
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)->get('/admin/reports')->assertOk();
        $this->actingAs($user)->get('/admin')->assertForbidden();
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
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => 'password123',
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->assertTrue($user->hasPermission('roles.update'));
    }

    public function test_report_viewer_is_redirected_to_first_permitted_page_after_login(): void
    {
        $role = Role::create([
            'name' => 'Report Viewer',
            'slug' => 'report-viewer',
            'permissions' => ['reports.view'],
        ]);

        User::create([
            'name' => 'Report Viewer',
            'email' => 'reports@example.com',
            'password' => 'password123',
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->post('/login', [
            'email' => 'reports@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('admin.reports.index'));
    }
}
