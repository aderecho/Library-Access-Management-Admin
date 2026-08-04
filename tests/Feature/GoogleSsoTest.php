<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleSsoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.google', [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect' => 'http://localhost/api/auth/google/callback',
        ]);
    }

    public function test_manual_login_endpoint_is_not_available(): void
    {
        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertMethodNotAllowed();
    }

    public function test_local_environment_uses_api_google_callback_path(): void
    {
        $this->assertSame('/api/auth/google/callback', parse_url(route('login.google.callback'), PHP_URL_PATH));
    }

    public function test_guest_can_start_google_sign_in(): void
    {
        Socialite::fake('google');

        $this->get(route('login.google'))->assertRedirect();
    }

    public function test_existing_active_user_can_sign_in_with_google(): void
    {
        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'permissions' => [],
        ]);

        $user = User::factory()->create([
            'email' => 'admin@up.edu.ph',
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        Socialite::fake('google', (new SocialiteUser)->map([
            'id' => 'google-user-1',
            'name' => 'UP Cebu Admin',
            'email' => 'ADMIN@UP.EDU.PH',
            'avatar' => 'https://lh3.googleusercontent.com/test-avatar',
        ]));

        $this->get(route('login.google.callback'))
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('auth.google_avatar', 'https://lh3.googleusercontent.com/test-avatar');

        $this->assertAuthenticatedAs($user);
    }

    public function test_unknown_google_account_is_rejected(): void
    {
        Socialite::fake('google', (new SocialiteUser)->map([
            'id' => 'google-user-2',
            'name' => 'Unknown User',
            'email' => 'unknown@example.com',
        ]));

        $this->get(route('login.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'Google signed in as unknown@example.com, but that exact email is not an active system account.',
            ]);

        $this->assertGuest();
    }

    public function test_inactive_google_account_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'inactive@up.edu.ph',
            'is_active' => false,
        ]);

        Socialite::fake('google', (new SocialiteUser)->map([
            'id' => 'google-user-3',
            'name' => 'Inactive User',
            'email' => 'inactive@up.edu.ph',
        ]));

        $this->get(route('login.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
