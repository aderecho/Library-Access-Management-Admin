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
            'media' => UploadedFile::fake()->image('welcome.jpg', 1600, 900),
        ]);

        $response->assertRedirect(route('admin.advertisements.index'));

        $advertisement = Advertisement::firstOrFail();
        $this->assertSame('Campus Welcome Week', $advertisement->title);
        $this->assertSame('image', $advertisement->media_type);
        Storage::disk('public')->assertExists($advertisement->image_path);
    }

    public function test_authorized_user_can_upload_an_advertisement_video(): void
    {
        Storage::fake('public');
        $role = Role::create([
            'name' => 'Video Advertisement Manager',
            'slug' => 'video-advertisement-manager',
            'permissions' => ['advertisements.create'],
        ]);
        $user = User::factory()->create(['role_id' => $role->id, 'is_active' => true]);

        $this->actingAs($user)->post(route('admin.advertisements.store'), [
            'title' => 'Campus Video',
            'media' => UploadedFile::fake()->create('campus.mp4', 2048, 'video/mp4'),
        ])->assertRedirect(route('admin.advertisements.index'));

        $advertisement = Advertisement::firstOrFail();
        $this->assertSame('video', $advertisement->media_type);
        Storage::disk('public')->assertExists($advertisement->image_path);
    }

    public function test_advertisement_video_at_five_hundred_megabytes_is_accepted(): void
    {
        Storage::fake('public');
        $user = $this->createAdvertisementUser(['advertisements.create']);

        $this->actingAs($user)->post(route('admin.advertisements.store'), [
            'title' => 'Maximum size campus video',
            'media' => UploadedFile::fake()->create('maximum.mp4', 512000, 'video/mp4'),
        ])->assertRedirect(route('admin.advertisements.index'));

        $advertisement = Advertisement::firstOrFail();
        $this->assertSame('video', $advertisement->media_type);
        Storage::disk('public')->assertExists($advertisement->image_path);
    }

    public function test_advertisement_video_over_five_hundred_megabytes_is_rejected_with_a_clear_warning(): void
    {
        Storage::fake('public');
        $user = $this->createAdvertisementUser(['advertisements.view', 'advertisements.create']);

        $this->actingAs($user)->post(route('admin.advertisements.store'), [
            'title' => 'Oversized campus video',
            'media' => UploadedFile::fake()->create('oversized.mp4', 512001, 'video/mp4'),
        ])->assertSessionHasErrors([
            'media' => 'The video must not exceed 500 MB.',
        ]);

        $this->assertDatabaseCount('advertisements', 0);
    }

    public function test_advertisement_image_limit_remains_fifty_megabytes(): void
    {
        Storage::fake('public');
        $user = $this->createAdvertisementUser(['advertisements.create']);

        $this->actingAs($user)->post(route('admin.advertisements.store'), [
            'title' => 'Oversized campus image',
            'media' => UploadedFile::fake()->create('oversized.jpg', 51201, 'image/jpeg'),
        ])->assertSessionHasErrors([
            'media' => 'The image must not exceed 50 MB.',
        ]);

        $this->assertDatabaseCount('advertisements', 0);
    }

    public function test_advertisement_form_exposes_the_client_side_media_limit_warning(): void
    {
        $user = $this->createAdvertisementUser(['advertisements.view', 'advertisements.create']);

        $this->actingAs($user)
            ->get(route('admin.advertisements.index'))
            ->assertOk()
            ->assertSee('data-max-image-bytes="52428800"', false)
            ->assertSee('data-max-video-bytes="524288000"', false)
            ->assertSee('data-ad-media-warning', false)
            ->assertSee('JPG, PNG or WebP up to 50 MB')
            ->assertSee('MP4 or WebM up to 500 MB');

        $script = file_get_contents(resource_path('js/advertisements.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('file.size <= maxBytes', $script);
        $this->assertStringContainsString('input.dataset.maxVideoBytes', $script);
        $this->assertStringContainsString('input.dataset.maxImageBytes', $script);
        $this->assertStringContainsString("new CustomEvent('admin:notify'", $script);
        $this->assertStringContainsString('The maximum upload size is', $script);
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
            ->assertSee('Advertisement library')
            ->assertSee('Published')
            ->assertSee('Scheduled')
            ->assertSee('Expired')
            ->assertDontSee('Publish advertisement');
    }

    public function test_advertisements_are_segregated_by_lifecycle(): void
    {
        $role = Role::create([
            'name' => 'Advertisement Lifecycle Viewer',
            'slug' => 'advertisement-lifecycle-viewer',
            'permissions' => ['advertisements.view'],
        ]);
        $user = User::factory()->create(['role_id' => $role->id, 'is_active' => true]);

        Advertisement::create([
            'title' => 'Published Notice',
            'image_path' => 'advertisements/published.jpg',
            'media_type' => 'image',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);
        Advertisement::create([
            'title' => 'Scheduled Notice',
            'image_path' => 'advertisements/scheduled.jpg',
            'media_type' => 'image',
            'starts_at' => now()->addDay(),
        ]);
        Advertisement::create([
            'title' => 'Expired Notice',
            'image_path' => 'advertisements/expired.jpg',
            'media_type' => 'image',
            'ends_at' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.advertisements.index', ['status' => 'published']))
            ->assertOk()
            ->assertSee('Published Notice')
            ->assertDontSee('Scheduled Notice')
            ->assertDontSee('Expired Notice');

        $this->actingAs($user)
            ->get(route('admin.advertisements.index', ['status' => 'scheduled']))
            ->assertOk()
            ->assertSee('Scheduled Notice')
            ->assertDontSee('Published Notice')
            ->assertDontSee('Expired Notice');

        $this->actingAs($user)
            ->get(route('admin.advertisements.index', ['status' => 'expired']))
            ->assertOk()
            ->assertSee('Expired Notice')
            ->assertDontSee('Published Notice')
            ->assertDontSee('Scheduled Notice');
    }

    public function test_advertisement_manager_can_edit_details_without_replacing_media(): void
    {
        Storage::fake('public');
        $user = $this->createAdvertisementUser(['advertisements.view', 'advertisements.create']);
        $advertisement = Advertisement::create([
            'title' => 'Original title',
            'description' => 'Original description',
            'image_path' => 'advertisements/original.jpg',
            'media_type' => 'image',
        ]);
        Storage::disk('public')->put($advertisement->image_path, 'original image');

        $this->actingAs($user)->put(route('admin.advertisements.update', $advertisement), [
            'title' => 'Updated title',
            'description' => 'Updated description',
            'return_status' => 'published',
        ])->assertRedirect(route('admin.advertisements.index', ['status' => 'published']));

        $advertisement->refresh();
        $this->assertSame('Updated title', $advertisement->title);
        $this->assertSame('Updated description', $advertisement->description);
        $this->assertSame('advertisements/original.jpg', $advertisement->image_path);
        Storage::disk('public')->assertExists('advertisements/original.jpg');
    }

    public function test_advertisement_manager_can_replace_media_while_editing(): void
    {
        Storage::fake('public');
        $user = $this->createAdvertisementUser(['advertisements.create']);
        $advertisement = Advertisement::create([
            'title' => 'Image advertisement',
            'image_path' => 'advertisements/replace-me.jpg',
            'media_type' => 'image',
        ]);
        Storage::disk('public')->put($advertisement->image_path, 'old image');

        $this->actingAs($user)->put(route('admin.advertisements.update', $advertisement), [
            'title' => 'Video advertisement',
            'media' => UploadedFile::fake()->create('replacement.mp4', 2048, 'video/mp4'),
        ])->assertRedirect();

        $advertisement->refresh();
        $this->assertSame('video', $advertisement->media_type);
        $this->assertNotSame('advertisements/replace-me.jpg', $advertisement->image_path);
        Storage::disk('public')->assertMissing('advertisements/replace-me.jpg');
        Storage::disk('public')->assertExists($advertisement->image_path);
    }

    public function test_advertisement_manager_can_delete_an_advertisement_and_its_media(): void
    {
        Storage::fake('public');
        $user = $this->createAdvertisementUser(['advertisements.view', 'advertisements.create']);
        $advertisement = Advertisement::create([
            'title' => 'Advertisement to delete',
            'image_path' => 'advertisements/delete-me.mp4',
            'media_type' => 'video',
        ]);
        Storage::disk('public')->put($advertisement->image_path, 'video content');

        $this->actingAs($user)
            ->delete(route('admin.advertisements.destroy', $advertisement), ['return_status' => 'published'])
            ->assertRedirect(route('admin.advertisements.index', ['status' => 'published']))
            ->assertSessionHas('success', 'Advertisement deleted successfully.');

        $this->assertDatabaseMissing('advertisements', ['id' => $advertisement->id]);
        Storage::disk('public')->assertMissing('advertisements/delete-me.mp4');
    }

    public function test_edit_modal_exposes_the_delete_action_and_confirmation(): void
    {
        $user = $this->createAdvertisementUser(['advertisements.view', 'advertisements.create']);
        $advertisement = Advertisement::create([
            'title' => 'Deletable advertisement',
            'image_path' => 'advertisements/deletable.jpg',
            'media_type' => 'image',
        ]);

        $this->actingAs($user)
            ->get(route('admin.advertisements.index'))
            ->assertOk()
            ->assertSee('data-delete-url="'.route('admin.advertisements.destroy', $advertisement).'"', false)
            ->assertSee('data-ad-edit-delete', false)
            ->assertSee('data-ad-delete-dialog', false)
            ->assertSee('Keep advertisement')
            ->assertSee('Yes, delete');

        $script = file_get_contents(resource_path('js/advertisements.js'));

        $this->assertIsString($script);
        $this->assertStringNotContainsString('window.confirm', $script);
        $this->assertStringContainsString('deleteDialog.showModal()', $script);
    }

    public function test_read_only_advertisement_viewer_cannot_edit(): void
    {
        $user = $this->createAdvertisementUser(['advertisements.view']);
        $advertisement = Advertisement::create([
            'title' => 'Protected advertisement',
            'image_path' => 'advertisements/protected.jpg',
            'media_type' => 'image',
        ]);

        $this->actingAs($user)
            ->get(route('admin.advertisements.index'))
            ->assertOk()
            ->assertDontSee('data-edit-advertisement', false);

        $this->actingAs($user)->put(route('admin.advertisements.update', $advertisement), [
            'title' => 'Unauthorized edit',
        ])->assertForbidden();

        $this->actingAs($user)
            ->delete(route('admin.advertisements.destroy', $advertisement))
            ->assertForbidden();

        $this->assertSame('Protected advertisement', $advertisement->fresh()->title);
    }

    public function test_advertisement_requires_supported_media(): void
    {
        $role = Role::create([
            'name' => 'Advertisement Manager',
            'slug' => 'advertisement-manager',
            'permissions' => ['advertisements.create'],
        ]);
        $user = User::factory()->create(['role_id' => $role->id, 'is_active' => true]);

        $this->actingAs($user)->post(route('admin.advertisements.store'), [
            'title' => 'Invalid upload',
            'media' => UploadedFile::fake()->create('notice.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('media');
    }

    private function createAdvertisementUser(array $permissions): User
    {
        $role = Role::create([
            'name' => fake()->unique()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'permissions' => $permissions,
        ]);

        return User::factory()->create(['role_id' => $role->id, 'is_active' => true]);
    }
}
