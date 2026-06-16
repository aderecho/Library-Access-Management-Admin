<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_layout_contains_navigation_and_global_search(): void
    {
        $adminRole = Role::create([
            'name' => 'Admin',
            'slug' => 'admin',
            'permissions' => ['dashboard.view', 'transactions.view'],
        ]);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk()
            ->assertSee('aria-label="Admin toolbar"', false)
            ->assertSee('placeholder="Search"', false)
            ->assertSee(route('admin.transactions.index'), false)
            ->assertSee('Hi, Admin User');
    }
}
