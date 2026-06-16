<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\ScannerToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScannerTokenManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_admin_can_view_and_create_scanner_registration(): void
    {
        $role = Role::create([
            'name' => 'Scanner Admin',
            'slug' => 'scanner-admin',
            'permissions' => [
                'scanner-tokens.view',
                'scanner-tokens.create',
                'scanner-tokens.update',
            ],
        ]);

        $admin = User::create([
            'name' => 'Scanner Admin',
            'email' => 'scanner-admin@example.com',
            'password' => 'password123',
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.scanner-tokens.index'))
            ->assertOk()
            ->assertSee('Registered Scanners')
            ->assertSee('Generate token');

        $response = $this->actingAs($admin)->post(route('admin.scanner-tokens.store'), [
            'name' => 'Main Entrance Scanner',
            'device_id' => 'scanner-main-01',
        ]);

        $response->assertRedirect(route('admin.scanner-tokens.index'))
            ->assertSessionHas('generated_scanner_token', fn (string $token) => str_starts_with($token, 'upcebu_scanner_'));

        $scannerToken = ScannerToken::firstOrFail();

        $this->assertSame('Main Entrance Scanner', $scannerToken->name);
        $this->assertSame('scanner-main-01', $scannerToken->device_id);
        $this->assertTrue($scannerToken->is_active);
        $this->assertSame(64, strlen($scannerToken->token_hash));
    }
}
