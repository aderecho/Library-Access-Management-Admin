<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class ScannerSettingsSsoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('cache.default', 'array');
        config()->set('services.google', [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect' => 'http://localhost/api/auth/google/callback',
        ]);
        Cache::flush();
    }

    public function test_scanner_can_start_sso_request(): void
    {
        $response = $this->postJson(route('api.scanner.settings.sso.start'))
            ->assertOk()
            ->assertJsonStructure(['requestId', 'authorizationUrl', 'expiresInSeconds'])
            ->assertJsonPath('expiresInSeconds', 300);

        $this->assertStringContainsString(
            '/scanner/settings/authorize/'.$response->json('requestId'),
            $response->json('authorizationUrl')
        );
    }

    public function test_guest_is_sent_through_existing_sso_login(): void
    {
        $requestId = $this->startSsoRequest();

        $this->get(route('scanner.settings.authorize', $requestId))
            ->assertRedirect(route('login'));
    }

    public function test_authorized_sso_user_approves_one_time_request(): void
    {
        $requestId = $this->startSsoRequest();
        $user = $this->createUserWithPermissions(['scanner-tokens.update']);

        $this->actingAs($user)
            ->get(route('scanner.settings.authorize', $requestId))
            ->assertOk()
            ->assertSee('Scanner settings authorized');

        $this->getJson(route('api.scanner.settings.sso.status', $requestId))
            ->assertOk()
            ->assertJson(['authenticated' => true]);

        $this->getJson(route('api.scanner.settings.sso.status', $requestId))
            ->assertStatus(410);
    }

    public function test_google_callback_returns_to_scanner_approval(): void
    {
        $requestId = $this->startSsoRequest();
        $user = $this->createUserWithPermissions(['scanner-tokens.update']);

        $this->get(route('scanner.settings.authorize', $requestId))
            ->assertRedirect(route('login'));

        Socialite::fake('google', (new SocialiteUser)->map([
            'id' => 'scanner-settings-admin',
            'name' => $user->name,
            'email' => $user->email,
        ]));

        $this->get(route('login.google.callback'))
            ->assertRedirect(route('scanner.settings.authorize', $requestId));

        $this->get(route('scanner.settings.authorize', $requestId))
            ->assertOk()
            ->assertSee('Scanner settings authorized');

        $this->getJson(route('api.scanner.settings.sso.status', $requestId))
            ->assertOk()
            ->assertJson(['authenticated' => true]);
    }

    public function test_pending_request_does_not_authorize_settings(): void
    {
        $requestId = $this->startSsoRequest();

        $this->getJson(route('api.scanner.settings.sso.status', $requestId))
            ->assertStatus(202)
            ->assertJson([
                'authenticated' => false,
                'status' => 'pending',
            ]);
    }

    public function test_user_without_scanner_update_permission_is_denied(): void
    {
        $requestId = $this->startSsoRequest();
        $user = $this->createUserWithPermissions(['scanner-tokens.view']);

        $this->actingAs($user)
            ->get(route('scanner.settings.authorize', $requestId))
            ->assertOk()
            ->assertSee('Access denied');

        $this->getJson(route('api.scanner.settings.sso.status', $requestId))
            ->assertStatus(202)
            ->assertJson(['authenticated' => false]);
    }

    private function startSsoRequest(): string
    {
        return $this->postJson(route('api.scanner.settings.sso.start'))->json('requestId');
    }

    private function createUserWithPermissions(array $permissions): User
    {
        $role = Role::query()->create([
            'name' => fake()->unique()->words(2, true),
            'slug' => fake()->unique()->slug(),
            'permissions' => $permissions,
        ]);

        return User::factory()->create([
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }
}
