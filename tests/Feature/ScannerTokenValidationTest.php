<?php

namespace Tests\Feature;

use App\Models\ScannerToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScannerTokenValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_scanner_token_can_be_validated(): void
    {
        [$token, $scanner] = $this->createScannerToken();

        $this->withHeader('X-Scanner-Token', $token)
            ->getJson(route('api.scanner.validate'))
            ->assertOk()
            ->assertJson([
                'registered' => true,
                'active' => true,
                'scanner' => [
                    'name' => 'Main Entrance Scanner',
                    'deviceId' => 'scanner-main-01',
                ],
            ])
            ->assertJsonMissingPath('expiresAt');

        $this->assertNotNull($scanner->fresh()->last_used_at);
    }

    public function test_missing_or_invalid_scanner_token_is_rejected(): void
    {
        $this->getJson(route('api.scanner.validate'))
            ->assertUnauthorized()
            ->assertJson(['message' => 'Scanner token is required.']);

        $this->withHeader('X-Scanner-Token', 'invalid-token')
            ->getJson(route('api.scanner.validate'))
            ->assertUnauthorized()
            ->assertJson(['message' => 'Scanner application is not registered.']);
    }

    public function test_deactivated_scanner_token_is_rejected(): void
    {
        [$token] = $this->createScannerToken([
            'is_active' => false,
        ]);

        $this->withHeader('X-Scanner-Token', $token)
            ->getJson(route('api.scanner.validate'))
            ->assertForbidden()
            ->assertJson([
                'message' => 'Scanner registration is deactivated.',
            ]);
    }

    private function createScannerToken(array $attributes = []): array
    {
        $token = 'upcebu_scanner_validation_test_'.fake()->unique()->numerify('####');

        $scanner = ScannerToken::create([
            'name' => 'Main Entrance Scanner',
            'device_id' => 'scanner-main-01',
            'token_hash' => hash('sha256', $token),
            'token_prefix' => substr($token, 0, 22),
            'is_active' => true,
            ...$attributes,
        ]);

        return [$token, $scanner];
    }
}
