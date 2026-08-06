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

        $response = $this
            ->withSession(['auth.google_avatar' => 'https://lh3.googleusercontent.com/avatar.jpg'])
            ->actingAs($admin)
            ->get('/admin');

        $response->assertOk()
            ->assertSee('aria-label="Primary navigation"', false)
            ->assertSee('class="nav-icon"', false)
            ->assertSee('aria-label="Admin toolbar"', false)
            ->assertSee('placeholder="Search"', false)
            ->assertSee(route('admin.transactions.index'), false)
            ->assertSee('Admin User')
            ->assertSee('https://lh3.googleusercontent.com/avatar.jpg', false)
            ->assertSee('Admin User profile photo');

        $stylesheet = file_get_contents(public_path('css/admin.css'));
        $this->assertIsString($stylesheet);
        $this->assertStringContainsString('--font-ui:Arial,Helvetica,sans-serif', $stylesheet);
        $this->assertStringNotContainsString('font-family:Georgia,serif', $stylesheet);
        $this->assertStringContainsString('height:100dvh;overflow:hidden', $stylesheet);
        $this->assertStringContainsString('@media(min-width:951px) and (max-height:700px)', $stylesheet);
    }
}
