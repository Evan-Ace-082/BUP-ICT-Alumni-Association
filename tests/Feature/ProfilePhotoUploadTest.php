<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_image_is_uploaded_and_saved_to_the_alumni_record(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $alumni = Alumni::factory()->active()->create(['user_id' => $user->id]);

        $photo = UploadedFile::fake()->image('avatar.jpg', 400, 400)->size(500); // 500 KB

        $response = $this->actingAs($user)->put('/dashboard/profile', [
            'email' => $user->email,
            'photo' => $photo,
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();

        $alumni->refresh();
        $this->assertNotNull($alumni->profile_photo);
        $this->assertStringStartsWith('alumni-photos/', $alumni->profile_photo);
        Storage::disk('public')->assertExists($alumni->profile_photo);
    }

    public function test_a_non_image_file_is_rejected(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $alumni = Alumni::factory()->active()->create(['user_id' => $user->id]);

        $document = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)->put('/dashboard/profile', [
            'email' => $user->email,
            'photo' => $document,
        ]);

        $response->assertSessionHasErrors('photo');
        $this->assertNull($alumni->fresh()->profile_photo);
    }

    public function test_an_oversized_photo_is_rejected(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $alumni = Alumni::factory()->active()->create(['user_id' => $user->id]);

        $tooBig = UploadedFile::fake()->image('huge.jpg')->size(3000); // 3000 KB > 2048 KB limit

        $response = $this->actingAs($user)->put('/dashboard/profile', [
            'email' => $user->email,
            'photo' => $tooBig,
        ]);

        $response->assertSessionHasErrors('photo');
        $this->assertNull($alumni->fresh()->profile_photo);
    }

    public function test_uploading_a_new_photo_removes_the_old_one(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $alumni = Alumni::factory()->active()->create([
            'user_id' => $user->id,
            'profile_photo' => 'alumni-photos/old-photo.jpg',
        ]);
        Storage::disk('public')->put('alumni-photos/old-photo.jpg', 'fake-old-content');

        $newPhoto = UploadedFile::fake()->image('new.jpg')->size(400);

        $this->actingAs($user)->put('/dashboard/profile', [
            'email' => $user->email,
            'photo' => $newPhoto,
        ]);

        $alumni->refresh();
        Storage::disk('public')->assertMissing('alumni-photos/old-photo.jpg');
        Storage::disk('public')->assertExists($alumni->profile_photo);
    }

    public function test_profile_update_without_a_photo_leaves_the_existing_photo_untouched(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $alumni = Alumni::factory()->active()->create([
            'user_id' => $user->id,
            'profile_photo' => 'alumni-photos/existing.jpg',
        ]);
        Storage::disk('public')->put('alumni-photos/existing.jpg', 'fake-content');

        $this->actingAs($user)->put('/dashboard/profile', [
            'email' => $user->email,
            'company' => 'Example Corp',
        ]);

        $alumni->refresh();
        $this->assertEquals('alumni-photos/existing.jpg', $alumni->profile_photo);
        Storage::disk('public')->assertExists('alumni-photos/existing.jpg');
    }
}
