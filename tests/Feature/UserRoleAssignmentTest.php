<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_add_user_form_displays_role_permissions_and_assigns_selected_role(): void
    {
        $superAdminRole = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
        ]);

        $adminRole = Role::create([
            'name' => 'Admin',
            'slug' => 'admin',
            'description' => 'Dashboard and transaction access.',
            'permissions' => ['dashboard.view', 'transactions.view'],
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
            ->assertSee('Assign user role')
            ->assertSee('2 permissions')
            ->assertSee('2 allowed')
            ->assertDontSee('name="password"', false)
            ->assertDontSee('name="password_confirmation"', false);

        $response = $this->actingAs($superAdmin)->post('/admin/users', [
            'first_name' => 'New',
            'middle_name' => 'Portal',
            'last_name' => 'Admin',
            'suffix' => 'Jr.',
            'email' => 'new-admin@example.com',
            'role_id' => $adminRole->id,
            'branch_id' => $this->defaultBranch()->id,
            'is_active' => true,
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'new-admin@example.com',
            'first_name' => 'New',
            'middle_name' => 'Portal',
            'last_name' => 'Admin',
            'suffix' => 'Jr.',
            'role_id' => $adminRole->id,
            'branch_id' => $this->defaultBranch()->id,
        ]);

        $this->assertNotEmpty(User::where('email', 'new-admin@example.com')->value('password'));

        $this->actingAs($superAdmin)
            ->get($response->headers->get('Location'))
            ->assertSee('data-notification', false)
            ->assertSee('User account created.');
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
            'role_id' => $monitorRole->id,
            'is_active' => true,
        ])->assertSessionHasErrors('branch_id');

        $this->assertDatabaseMissing('users', ['email' => 'unassigned@example.com']);
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
