<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_entry_monitor_role_only_accesses_its_branch_monitor(): void
    {
        $role = Role::where('slug', 'entry-monitor')->firstOrFail();

        $this->assertEqualsCanonicalizing(['entry-monitor.view'], $role->permissions);

        $user = User::factory()->create([
            'branch_id' => $this->defaultBranch()->id,
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('admin.entry-monitor'))
            ->assertOk()
            ->assertSee('Entry Monitor')
            ->assertSee($this->defaultBranch()->name)
            ->assertDontSee(route('admin.transactions.index'), false);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.entry-monitor'));

        $this->actingAs($user)
            ->get(route('admin.transactions.index'))
            ->assertForbidden();
    }

    public function test_itc_tech_role_has_branch_configuration_permissions(): void
    {
        $this->assertDatabaseHas('roles', [
            'name' => 'ITC-Tech',
            'slug' => 'itc-tech',
        ]);

        $role = Role::where('slug', 'itc-tech')->firstOrFail();

        $this->assertEqualsCanonicalizing([
            'branches.view',
            'branches.create',
            'branches.update',
        ], $role->permissions);

        $user = User::factory()->create([
            'branch_id' => $this->defaultBranch()->id,
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('admin.branches.index'))
            ->assertOk()
            ->assertSee('Branch Configuration')
            ->assertSee('Create branch');
    }

    public function test_add_user_form_assigns_multiple_roles_and_combines_their_permissions(): void
    {
        $superAdminRole = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
        ]);

        $entryMonitorRole = Role::where('slug', 'entry-monitor')->firstOrFail();
        $reportRole = Role::create([
            'name' => 'Combined Report Viewer',
            'slug' => 'combined-report-viewer',
            'description' => 'Read-only report access.',
            'permissions' => ['reports.view'],
        ]);

        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => 'password123',
            'role_id' => $superAdminRole->id,
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->get('/admin/users/create')
            ->assertOk()
            ->assertSee('name="first_name"', false)
            ->assertSee('name="middle_name"', false)
            ->assertSee('name="last_name"', false)
            ->assertSee('name="suffix"', false)
            ->assertSee('Assign user roles')
            ->assertSee('Select one or more roles')
            ->assertSee('type="checkbox"', false)
            ->assertSee('name="role_ids[]"', false)
            ->assertDontSee('name="password"', false)
            ->assertDontSee('name="password_confirmation"', false);

        $this->actingAs($superAdmin)->post('/admin/users', [
            'first_name' => 'New',
            'middle_name' => 'Portal',
            'last_name' => 'Admin',
            'suffix' => 'Jr.',
            'email' => 'new-admin@example.com',
            'role_ids' => [$entryMonitorRole->id, $reportRole->id],
            'branch_id' => $this->defaultBranch()->id,
            'is_active' => true,
        ])->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'User account created.');

        $this->assertDatabaseHas('users', [
            'email' => 'new-admin@example.com',
            'first_name' => 'New',
            'middle_name' => 'Portal',
            'last_name' => 'Admin',
            'suffix' => 'Jr.',
            'branch_id' => $this->defaultBranch()->id,
        ]);

        $user = User::where('email', 'new-admin@example.com')->firstOrFail();
        $this->assertContains($user->role_id, [$entryMonitorRole->id, $reportRole->id]);
        $this->assertEqualsCanonicalizing(
            [$entryMonitorRole->id, $reportRole->id],
            $user->roles()->pluck('roles.id')->all()
        );
        $this->assertTrue($user->hasPermission('entry-monitor.view'));
        $this->assertTrue($user->hasPermission('reports.view'));
        $this->assertFalse($user->hasPermission('transactions.view'));

        $this->assertNotEmpty(User::where('email', 'new-admin@example.com')->value('password'));

        $this->actingAs($user)->get(route('admin.entry-monitor'))->assertOk();
        $this->actingAs($user)->get(route('admin.reports.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.transactions.index'))->assertForbidden();

        $this->actingAs($superAdmin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Combined Report Viewer, Entry Monitor');
    }

    public function test_non_super_admin_must_be_assigned_to_an_active_branch(): void
    {
        $superRole = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);
        $monitorRole = Role::create(['name' => 'Monitor', 'slug' => 'monitor']);
        $superAdmin = User::create(['name' => 'Super', 'email' => 'super-branch@example.com', 'password' => 'password123', 'role_id' => $superRole->id, 'is_active' => true]);

        $this->actingAs($superAdmin)->post('/admin/users', [
            'first_name' => 'Unassigned',
            'last_name' => 'Monitor',
            'email' => 'unassigned@example.com',
            'role_ids' => [$monitorRole->id],
            'is_active' => true,
        ])->assertSessionHasErrors('branch_id');

        $this->assertDatabaseMissing('users', ['email' => 'unassigned@example.com']);
    }

    public function test_edit_user_replaces_the_selected_role_combination(): void
    {
        $superRole = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);
        $entryRole = Role::where('slug', 'entry-monitor')->firstOrFail();
        $reportRole = Role::create([
            'name' => 'Edit Report Viewer',
            'slug' => 'edit-report-viewer',
            'permissions' => ['reports.view'],
        ]);
        $superAdmin = User::create(['name' => 'Super', 'email' => 'super-edit-roles@example.com', 'password' => 'password123', 'role_id' => $superRole->id, 'is_active' => true]);
        $user = User::create([
            'branch_id' => $this->defaultBranch()->id,
            'first_name' => 'Multiple',
            'last_name' => 'Roles',
            'email' => 'edit-roles@example.com',
            'password' => 'password123',
            'role_id' => $entryRole->id,
            'is_active' => true,
        ]);
        $user->roles()->sync([$entryRole->id, $reportRole->id]);

        $this->actingAs($superAdmin)->put(route('admin.users.update', $user), [
            'first_name' => 'Multiple',
            'last_name' => 'Roles',
            'email' => 'edit-roles@example.com',
            'role_ids' => [$reportRole->id],
            'branch_id' => $this->defaultBranch()->id,
            'is_active' => true,
        ])->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertSame([$reportRole->id], $user->roles()->pluck('roles.id')->all());
        $this->assertFalse($user->hasPermission('entry-monitor.view'));
        $this->assertTrue($user->hasPermission('reports.view'));
    }

    public function test_user_must_have_at_least_one_role(): void
    {
        $superRole = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);
        $superAdmin = User::create(['name' => 'Super', 'email' => 'super-no-role@example.com', 'password' => 'password123', 'role_id' => $superRole->id, 'is_active' => true]);

        $this->actingAs($superAdmin)->post('/admin/users', [
            'first_name' => 'Missing',
            'last_name' => 'Role',
            'email' => 'missing-role@example.com',
            'role_ids' => [],
            'branch_id' => $this->defaultBranch()->id,
            'is_active' => true,
        ])->assertSessionHasErrors('role_ids');

        $this->assertDatabaseMissing('users', ['email' => 'missing-role@example.com']);
    }

    public function test_user_account_list_displays_assigned_branch(): void
    {
        $superRole = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);
        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $superAdmin = User::create(['name' => 'Super', 'email' => 'super-list@example.com', 'password' => 'password123', 'role_id' => $superRole->id, 'is_active' => true]);
        User::create(['branch_id' => $this->defaultBranch()->id, 'name' => 'Tagged User', 'email' => 'tagged@example.com', 'password' => 'password123', 'role_id' => $adminRole->id, 'is_active' => true]);

        $this->actingAs($superAdmin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('First Name')
            ->assertSee('Middle Name')
            ->assertSee('Last Name')
            ->assertSee('Suffix')
            ->assertSee('Assigned Branch')
            ->assertSee($this->defaultBranch()->name);
    }
}
