<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionExpiryWarningTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_admin_pages_render_the_session_warning(): void
    {
        config()->set('session.lifetime', 30);
        config()->set('session.warning_seconds', 90);

        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('data-session-warning', false)
            ->assertSee('data-timeout-seconds="1800"', false)
            ->assertSee('data-warning-seconds="90"', false)
            ->assertSee('Your session is about to expire')
            ->assertSee(route('session.keep-alive'), false);
    }

    public function test_authenticated_user_can_extend_the_session(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->postJson(route('session.keep-alive'))
            ->assertOk()
            ->assertJsonStructure(['message', 'expires_at'])
            ->assertJson(['message' => 'Session extended.']);

        $this->assertNotNull(session('session_last_extended_at'));
    }

    public function test_guest_cannot_extend_a_session(): void
    {
        $this->postJson(route('session.keep-alive'))
            ->assertUnauthorized();
    }

    private function adminUser(): User
    {
        $role = Role::query()->create([
            'name' => 'Admin',
            'slug' => 'admin',
            'permissions' => ['dashboard.view'],
        ]);

        return User::factory()->create([
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }
}
