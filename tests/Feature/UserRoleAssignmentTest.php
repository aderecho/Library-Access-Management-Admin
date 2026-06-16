<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

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
            ->assertSee('Assign user role')
            ->assertSee('2 permissions')
            ->assertSee('2 allowed');

        $response = $this->actingAs($superAdmin)->post('/admin/users', [
            'name' => 'New Admin',
            'email' => 'new-admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $adminRole->id,
            'is_active' => true,
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'new-admin@example.com',
            'role_id' => $adminRole->id,
        ]);

        $this->actingAs($superAdmin)
            ->get($response->headers->get('Location'))
            ->assertSee('data-notification', false)
            ->assertSee('User account created.');
    }
}
