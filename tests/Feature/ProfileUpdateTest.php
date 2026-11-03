<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_alumni_can_update_editable_profile_fields(): void
    {
        $user = User::factory()->create();
        $alumni = Alumni::factory()->active()->create([
            'user_id' => $user->id,
            'student_id' => 'ORIGINAL-ID',
            'full_name' => 'Original Name',
        ]);

        $response = $this->actingAs($user)->put('/dashboard/profile', [
            'email' => 'updated@example.com',
            'phone' => '01900000000',
            'company' => 'Example Corp',
            'designation' => 'Software Engineer',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'updated@example.com', 'phone' => '01900000000']);
        $this->assertDatabaseHas('alumni', ['id' => $alumni->id, 'company' => 'Example Corp', 'designation' => 'Software Engineer']);
    }

    public function test_official_identity_fields_cannot_be_changed_via_profile_update(): void
    {
        $user = User::factory()->create();
        $alumni = Alumni::factory()->active()->create([
            'user_id' => $user->id,
            'student_id' => 'ORIGINAL-ID',
            'full_name' => 'Original Name',
        ]);

        $this->actingAs($user)->put('/dashboard/profile', [
            'email' => 'updated@example.com',
            'student_id' => 'TAMPERED-ID',
            'full_name' => 'Tampered Name',
        ]);

        $alumni->refresh();
        $this->assertEquals('ORIGINAL-ID', $alumni->student_id);
        $this->assertEquals('Original Name', $alumni->full_name);
    }

    public function test_guest_cannot_access_the_profile_page(): void
    {
        $this->get('/dashboard/profile')->assertRedirect(route('login'));
    }

    // ------------------------------------------------------------------
    // Phase 4: new self-editable fields
    // ------------------------------------------------------------------

    public function test_alumni_can_update_country_and_city(): void
    {
        $user = User::factory()->create();
        $alumni = Alumni::factory()->active()->create(['user_id' => $user->id]);

        $this->actingAs($user)->put('/dashboard/profile', [
            'email' => $user->email,
            'country' => 'Bangladesh',
            'city' => 'Dhaka',
        ]);

        $this->assertDatabaseHas('alumni', ['id' => $alumni->id, 'country' => 'Bangladesh', 'city' => 'Dhaka']);
    }

    public function test_alumni_can_update_skills(): void
    {
        $user = User::factory()->create();
        $alumni = Alumni::factory()->active()->create(['user_id' => $user->id]);

        $this->actingAs($user)->put('/dashboard/profile', [
            'email' => $user->email,
            'skills' => 'PHP, Laravel, Project Management',
        ]);

        $this->assertDatabaseHas('alumni', ['id' => $alumni->id, 'skills' => 'PHP, Laravel, Project Management']);
    }

    public function test_alumni_can_update_job_status(): void
    {
        $user = User::factory()->create();
        $alumni = Alumni::factory()->active()->create(['user_id' => $user->id]);

        $this->actingAs($user)->put('/dashboard/profile', [
            'email' => $user->email,
            'job_status' => 'Employed',
        ]);

        $this->assertDatabaseHas('alumni', ['id' => $alumni->id, 'job_status' => 'Employed']);
    }

    public function test_job_status_must_be_one_of_the_allowed_values(): void
    {
        $user = User::factory()->create();
        Alumni::factory()->active()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put('/dashboard/profile', [
            'email' => $user->email,
            'job_status' => 'Definitely Not A Real Status',
        ]);

        $response->assertSessionHasErrors('job_status');
    }

    public function test_alumni_can_enable_mentorship(): void
    {
        $user = User::factory()->create();
        $alumni = Alumni::factory()->active()->create(['user_id' => $user->id, 'mentorship' => false]);

        $this->actingAs($user)->put('/dashboard/profile', [
            'email' => $user->email,
            'mentorship' => '1',
        ]);

        $this->assertDatabaseHas('alumni', ['id' => $alumni->id, 'mentorship' => 1]);
    }

    public function test_alumni_can_disable_mentorship(): void
    {
        $user = User::factory()->create();
        $alumni = Alumni::factory()->active()->create(['user_id' => $user->id, 'mentorship' => true]);

        // The hidden-field pattern in the view submits '0' when the
        // checkbox is unchecked — simulated here directly.
        $this->actingAs($user)->put('/dashboard/profile', [
            'email' => $user->email,
            'mentorship' => '0',
        ]);

        $this->assertDatabaseHas('alumni', ['id' => $alumni->id, 'mentorship' => 0]);
    }

    public function test_official_identity_fields_still_cannot_be_changed_alongside_new_fields(): void
    {
        $user = User::factory()->create();
        $alumni = Alumni::factory()->active()->create([
            'user_id' => $user->id,
            'student_id' => 'ORIGINAL-ID',
            'full_name' => 'Original Name',
        ]);
        $batchId = $alumni->batch_id;

        $this->actingAs($user)->put('/dashboard/profile', [
            'email' => $user->email,
            'student_id' => 'TAMPERED-ID',
            'full_name' => 'Tampered Name',
            'batch_id' => $batchId + 999,
            'country' => 'Canada',
        ]);

        $alumni->refresh();
        $this->assertEquals('ORIGINAL-ID', $alumni->student_id);
        $this->assertEquals('Original Name', $alumni->full_name);
        $this->assertEquals($batchId, $alumni->batch_id);
        $this->assertEquals('Canada', $alumni->country);
    }
}
