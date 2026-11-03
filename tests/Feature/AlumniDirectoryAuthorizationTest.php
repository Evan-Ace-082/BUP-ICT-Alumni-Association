<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Batch;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlumniDirectoryAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function userWithRole(string $slug, array $attributes = []): User
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create($attributes);
        $user->roles()->attach(Role::where('slug', $slug)->firstOrFail());

        return $user;
    }

    public function test_a_guest_can_access_the_directory(): void
    {
        $this->get('/alumni')->assertOk();
    }

    public function test_a_guest_can_access_a_public_alumni_profile(): void
    {
        $alumni = Alumni::factory()->create();

        $this->get("/alumni/{$alumni->id}")->assertOk();
    }

    public function test_a_guest_cannot_see_the_alumnis_email(): void
    {
        $alumni = Alumni::factory()->create(['email' => 'super-secret-email@example.com']);

        $this->get("/alumni/{$alumni->id}")->assertDontSee('super-secret-email@example.com');
    }

    public function test_a_guest_cannot_see_the_alumnis_phone(): void
    {
        $alumni = Alumni::factory()->create(['phone' => '01799999999']);

        $this->get("/alumni/{$alumni->id}")->assertDontSee('01799999999');
    }

    public function test_a_guest_cannot_see_the_alumnis_address(): void
    {
        $alumni = Alumni::factory()->create(['address' => '123 Very Private Street']);

        $this->get("/alumni/{$alumni->id}")->assertDontSee('123 Very Private Street');
    }

    public function test_the_directory_listing_never_exposes_email_or_phone(): void
    {
        Alumni::factory()->create(['email' => 'listing-secret@example.com', 'phone' => '01788888888']);

        $response = $this->get('/alumni');

        $response->assertDontSee('listing-secret@example.com');
        $response->assertDontSee('01788888888');
    }

    public function test_a_logged_in_alumnus_can_also_view_public_profiles(): void
    {
        $viewer = User::factory()->create();
        Alumni::factory()->create(['user_id' => $viewer->id]);
        $other = Alumni::factory()->create();

        $this->actingAs($viewer)
            ->get("/alumni/{$other->id}")
            ->assertOk();
    }

    public function test_an_alumnus_cannot_update_another_alumnis_profile_through_the_self_service_route(): void
    {
        $viewer = User::factory()->create();
        Alumni::factory()->active()->create(['user_id' => $viewer->id]);

        $other = Alumni::factory()->create(['company' => 'Original Company']);

        // The self-service route has no {alumni} parameter — it always
        // resolves Auth::user()->alumni — so there is structurally no
        // way to target another alumni's record through it. This test
        // confirms the other record is untouched no matter what the
        // logged-in user submits.
        $this->actingAs($viewer)->put('/dashboard/profile', [
            'email' => $viewer->email,
            'company' => 'Hijacked Company',
        ]);

        $this->assertDatabaseHas('alumni', ['id' => $other->id, 'company' => 'Original Company']);
    }

    public function test_admin_access_to_the_admin_alumni_directory_remains_valid(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->get('/admin/alumni')->assertOk();
    }

    public function test_super_admin_access_to_the_admin_alumni_directory_remains_valid(): void
    {
        $superAdmin = $this->userWithRole('super-admin');

        $this->actingAs($superAdmin)->get('/admin/alumni')->assertOk();
    }

    public function test_batch_representative_boundaries_remain_valid_after_phase_4(): void
    {
        $ownBatch = Batch::factory()->create();
        $otherBatch = Batch::factory()->create();
        $rep = $this->userWithRole('batch-representative', ['assigned_batch_id' => $ownBatch->id]);

        $ownAlumni = Alumni::factory()->create(['batch_id' => $ownBatch->id]);
        $otherAlumni = Alumni::factory()->create(['batch_id' => $otherBatch->id]);

        $this->actingAs($rep)->get("/batch-rep/alumni/{$ownAlumni->id}/edit")->assertOk();
        $this->actingAs($rep)->get("/batch-rep/alumni/{$otherAlumni->id}/edit")->assertForbidden();
    }
}
