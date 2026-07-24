<?php

namespace Tests\Feature;

use App\Models\Advertisement;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdvertisementManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_upload_an_advertisement_image(): void
    {
        Storage::fake('public');

        $role = Role::create([
            'name' => 'Advertisement Manager',
            'slug' => 'advertisement-manager',
            'permissions' => ['advertisements.view', 'advertisements.create'],
        ]);

        $user = User::factory()->create(['role_id' => $role->id, 'is_active' => true]);

        $response = $this->actingAs($user)->post(route('admin.advertisements.store'), [
            'title' => 'Campus Welcome Week',
            'description' => 'Welcome activities for new students.',
            'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'image' => UploadedFile::fake()->image('welcome.jpg', 1600, 900),
        ]);

        $response->assertRedirect(route('admin.advertisements.index'));

        $advertisement = Advertisement::firstOrFail();
        $this->assertSame('Campus Welcome Week', $advertisement->title);
        Storage::disk('public')->assertExists($advertisement->image_path);
    }

    public function test_authorized_user_can_view_the_advertisement_workspace(): void
    {
        $role = Role::create([
            'name' => 'Advertisement Viewer',
            'slug' => 'advertisement-viewer',
            'permissions' => ['advertisements.view'],
        ]);
        $user = User::factory()->create(['role_id' => $role->id, 'is_active' => true]);

        $this->actingAs($user)
            ->get(route('admin.advertisements.index'))
            ->assertOk()
            ->assertSee('Published Advertisements')
            ->assertDontSee('Publish advertisement');
    }

    public function test_advertisement_requires_a_supported_image(): void
    {
        $role = Role::create([
            'name' => 'Advertisement Manager',
            'slug' => 'advertisement-manager',
            'permissions' => ['advertisements.create'],
        ]);
        $user = User::factory()->create(['role_id' => $role->id, 'is_active' => true]);

        $this->actingAs($user)->post(route('admin.advertisements.store'), [
            'title' => 'Invalid upload',
            'image' => UploadedFile::fake()->create('notice.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('image');
    }
}
