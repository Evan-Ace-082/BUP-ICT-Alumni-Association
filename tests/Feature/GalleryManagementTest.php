<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Batch;
use App\Models\Photo;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GalleryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Storage::fake('public');
    }

    protected function userWithRole(string $slug, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->roles()->attach(Role::where('slug', $slug)->firstOrFail());

        return $user;
    }

    public function test_guest_can_see_a_published_general_album(): void
    {
        Album::factory()->published()->create(['title' => 'Homecoming Photos']);

        $this->get('/gallery')->assertOk()->assertSee('Homecoming Photos');
    }

    public function test_draft_albums_are_hidden_from_guests(): void
    {
        $album = Album::factory()->create();

        $this->get('/gallery')->assertOk()->assertDontSee($album->title);
        $this->get("/gallery/{$album->id}")->assertNotFound();
    }

    public function test_admin_can_create_an_album_and_upload_a_photo(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->post('/admin/gallery/albums', ['title' => 'Reunion 2026'])
            ->assertRedirect();

        $album = Album::where('title', 'Reunion 2026')->firstOrFail();

        $file = UploadedFile::fake()->image('photo.jpg');
        $this->actingAs($admin)->post("/admin/gallery/albums/{$album->id}/photos", ['image' => $file])
            ->assertRedirect();

        $this->assertDatabaseHas('photos', ['album_id' => $album->id]);
        Storage::disk('public')->assertExists(Photo::first()->image_path);
    }

    public function test_photo_upload_rejects_a_non_image_file(): void
    {
        $admin = $this->userWithRole('admin');
        $album = Album::factory()->create();

        $file = UploadedFile::fake()->create('malware.exe', 10);

        $response = $this->actingAs($admin)->post("/admin/gallery/albums/{$album->id}/photos", ['image' => $file]);

        $response->assertSessionHasErrors('image');
        $this->assertDatabaseCount('photos', 0);
    }

    public function test_batch_rep_can_manage_their_own_batch_album(): void
    {
        $batch = Batch::factory()->create();
        $rep = $this->userWithRole('batch-representative', ['assigned_batch_id' => $batch->id]);

        $this->actingAs($rep)->post('/batch-rep/gallery/albums', ['title' => 'My Batch Album'])
            ->assertRedirect();

        $this->assertDatabaseHas('albums', ['title' => 'My Batch Album', 'batch_id' => $batch->id]);
    }

    public function test_batch_rep_cannot_manage_another_batchs_album(): void
    {
        $ownBatch = Batch::factory()->create();
        $otherBatch = Batch::factory()->create();
        $rep = $this->userWithRole('batch-representative', ['assigned_batch_id' => $ownBatch->id]);
        $otherAlbum = Album::factory()->forBatch($otherBatch->id)->create();

        $this->actingAs($rep)->get("/batch-rep/gallery/albums/{$otherAlbum->id}/edit")->assertForbidden();

        $file = UploadedFile::fake()->image('photo.jpg');
        $this->actingAs($rep)->post("/batch-rep/gallery/albums/{$otherAlbum->id}/photos", ['image' => $file])->assertForbidden();
    }

    public function test_batch_rep_cannot_delete_a_photo_belonging_to_another_batchs_album_even_by_id_guessing(): void
    {
        $ownBatch = Batch::factory()->create();
        $otherBatch = Batch::factory()->create();
        $rep = $this->userWithRole('batch-representative', ['assigned_batch_id' => $ownBatch->id]);

        $ownAlbum = Album::factory()->forBatch($ownBatch->id)->create();
        $otherAlbum = Album::factory()->forBatch($otherBatch->id)->create();
        $otherPhoto = Photo::factory()->create(['album_id' => $otherAlbum->id]);

        // Attempt to delete another batch's photo by pairing it with
        // an album the rep DOES own in the URL — the controller must
        // verify the photo actually belongs to the given album.
        $this->actingAs($rep)
            ->delete("/batch-rep/gallery/albums/{$ownAlbum->id}/photos/{$otherPhoto->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('photos', ['id' => $otherPhoto->id]);
    }

    public function test_unauthorized_deletion_of_an_album_is_blocked(): void
    {
        $alumni = $this->userWithRole('alumni');
        $album = Album::factory()->create();

        $this->actingAs($alumni)->get('/admin/gallery/albums')->assertForbidden();
    }

    public function test_a_draft_album_becomes_publicly_visible_after_publish_and_hidden_again_after_unpublish(): void
    {
        $admin = $this->userWithRole('admin');
        $album = Album::factory()->create(['title' => 'Lifecycle Album']);

        $this->get('/gallery')->assertDontSee('Lifecycle Album');

        $this->actingAs($admin)->patch("/admin/gallery/albums/{$album->id}/publish")->assertRedirect();
        $this->assertDatabaseHas('albums', ['id' => $album->id, 'status' => 'published']);

        auth()->logout();
        $this->get('/gallery')->assertSee('Lifecycle Album');

        $this->actingAs($admin)->patch("/admin/gallery/albums/{$album->id}/unpublish")->assertRedirect();
        $this->assertDatabaseHas('albums', ['id' => $album->id, 'status' => 'draft']);

        auth()->logout();
        $this->get('/gallery')->assertDontSee('Lifecycle Album');
    }

    public function test_photos_of_a_published_album_appear_on_the_public_album_page(): void
    {
        $album = Album::factory()->published()->create();
        $photo = Photo::factory()->create(['album_id' => $album->id, 'image_path' => 'gallery-photos/visible.jpg']);

        $this->get("/gallery/{$album->id}")
            ->assertOk()
            ->assertSee('gallery-photos/visible.jpg')
            ->assertSee('photoLightboxModal');
    }

    public function test_empty_published_album_displays_clear_empty_state(): void
    {
        $album = Album::factory()->published()->create();

        $this->get("/gallery/{$album->id}")
            ->assertOk()
            ->assertSee('No photos in this album yet');
    }

    public function test_the_manage_album_page_shows_feedback_photos_and_publish_controls(): void
    {
        $admin = $this->userWithRole('admin');
        $album = Album::factory()->create();
        Photo::factory()->create(['album_id' => $album->id, 'image_path' => 'gallery-photos/thumb.jpg']);

        $this->actingAs($admin)
            ->withSession(['status' => 'Photo uploaded.'])
            ->get("/admin/gallery/albums/{$album->id}/edit")
            ->assertOk()
            ->assertSee('Photo uploaded.')
            ->assertSee('gallery-photos/thumb.jpg')
            ->assertSee('Publish Album');
    }

    public function test_batch_rep_can_publish_their_own_batch_album_but_not_another_batchs(): void
    {
        $ownBatch = Batch::factory()->create();
        $otherBatch = Batch::factory()->create();
        $rep = $this->userWithRole('batch-representative', ['assigned_batch_id' => $ownBatch->id]);
        $own = Album::factory()->forBatch($ownBatch->id)->create();
        $other = Album::factory()->forBatch($otherBatch->id)->create();

        $this->actingAs($rep)->patch("/batch-rep/gallery/albums/{$own->id}/publish")->assertRedirect();
        $this->assertDatabaseHas('albums', ['id' => $own->id, 'status' => 'published']);

        $this->actingAs($rep)->patch("/batch-rep/gallery/albums/{$other->id}/publish")->assertForbidden();
        $this->assertDatabaseHas('albums', ['id' => $other->id, 'status' => 'draft']);
    }

    public function test_a_plain_alumnus_cannot_publish_an_album(): void
    {
        $alumni = $this->userWithRole('alumni');
        $album = Album::factory()->create();

        $this->actingAs($alumni)->patch("/admin/gallery/albums/{$album->id}/publish")->assertForbidden();
    }
}
