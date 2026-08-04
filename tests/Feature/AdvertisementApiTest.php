<?php

namespace Tests\Feature;

use App\Models\Advertisement;
use App\Models\ScannerToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvertisementApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_scanner_receives_only_currently_active_advertisements(): void
    {
        $token = 'upcebu_advertisement_api_test';
        ScannerToken::create([
            'branch_id' => $this->defaultBranch()->id,
            'name' => 'Advertisement Kiosk',
            'token_hash' => hash('sha256', $token),
            'token_prefix' => substr($token, 0, 22),
            'is_active' => true,
        ]);

        Advertisement::create([
            'title' => 'Active Campus Notice',
            'image_path' => 'advertisements/active.jpg',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);
        Advertisement::create([
            'title' => 'Future Campus Notice',
            'image_path' => 'advertisements/future.jpg',
            'starts_at' => now()->addDay(),
        ]);
        Advertisement::create([
            'title' => 'Ended Campus Notice',
            'image_path' => 'advertisements/ended.jpg',
            'ends_at' => now()->subDay(),
        ]);

        $this->withHeader('X-Scanner-Token', $token)
            ->getJson(route('api.advertisements.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Active Campus Notice')
            ->assertJsonPath('refreshAfterSeconds', 60)
            ->assertJsonMissing(['title' => 'Future Campus Notice'])
            ->assertJsonMissing(['title' => 'Ended Campus Notice']);
    }

    public function test_advertisement_endpoint_requires_scanner_token(): void
    {
        $this->getJson(route('api.advertisements.index'))
            ->assertUnauthorized();
    }
}
