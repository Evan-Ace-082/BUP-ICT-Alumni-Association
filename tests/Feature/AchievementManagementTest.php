<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\Alumni;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function alumniUser(): array
    {
        $user = User::factory()->create();
        $alumni = Alumni::factory()->active()->create(['user_id' => $user->id]);

        return [$user, $alumni];
    }

    protected function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'admin')->firstOrFail());

        return $admin;
    }

    public function test_alumni_can_submit_an_achievement(): void
    {
        [$user] = $this->alumniUser();

        $this->actingAs($user)->post('/dashboard/achievements', [
            'title' => 'Published a Paper',
            'description' => 'In an international journal.',
            'achievement_type' => 'Publication',
        ])->assertRedirect(route('dashboard.achievements.index'));

        $this->assertDatabaseHas('achievements', ['title' => 'Published a Paper', 'status' => Achievement::STATUS_PENDING]);
    }

    public function test_submitted_achievements_start_pending(): void
    {
        [$user] = $this->alumniUser();

        $this->actingAs($user)->post('/dashboard/achievements', [
            'title' => 'Award Winner', 'description' => 'Details.', 'achievement_type' => 'Award',
        ]);

        $this->assertDatabaseHas('achievements', ['title' => 'Award Winner', 'status' => 'pending']);
    }

    public function test_admin_can_approve_an_achievement(): void
    {
        $admin = $this->admin();
        $achievement = Achievement::factory()->create();

        $this->actingAs($admin)->patch("/admin/achievements/{$achievement->id}/approve")->assertRedirect();

        $this->assertDatabaseHas('achievements', ['id' => $achievement->id, 'status' => 'approved']);
    }

    public function test_admin_can_reject_an_achievement(): void
    {
        $admin = $this->admin();
        $achievement = Achievement::factory()->create();

        $this->actingAs($admin)->patch("/admin/achievements/{$achievement->id}/reject")->assertRedirect();

        $this->assertDatabaseHas('achievements', ['id' => $achievement->id, 'status' => 'rejected']);
    }

    public function test_alumni_cannot_approve_their_own_achievement(): void
    {
        [$user, $alumni] = $this->alumniUser();
        $achievement = Achievement::factory()->create(['alumni_id' => $alumni->id]);

        $this->actingAs($user)->patch("/admin/achievements/{$achievement->id}/approve")->assertForbidden();
    }

    public function test_public_sees_only_approved_achievements(): void
    {
        Achievement::factory()->approved()->create(['title' => 'Approved One']);
        Achievement::factory()->create(['title' => 'Still Pending']); // pending
        Achievement::factory()->rejected()->create(['title' => 'Was Rejected']);

        $response = $this->get('/achievements');

        $response->assertSee('Approved One');
        $response->assertDontSee('Still Pending');
        $response->assertDontSee('Was Rejected');
    }

    public function test_a_user_cannot_edit_another_alumnis_achievement(): void
    {
        [$owner, $ownerAlumni] = $this->alumniUser();
        [$intruder] = $this->alumniUser();
        $achievement = Achievement::factory()->create(['alumni_id' => $ownerAlumni->id]);

        $this->actingAs($intruder)->get("/dashboard/achievements/{$achievement->id}/edit")->assertForbidden();
    }

    public function test_alumni_can_edit_their_own_pending_achievement(): void
    {
        [$user, $alumni] = $this->alumniUser();
        $achievement = Achievement::factory()->create(['alumni_id' => $alumni->id, 'title' => 'Original Title']);

        $this->actingAs($user)->put("/dashboard/achievements/{$achievement->id}", [
            'title' => 'Updated Title', 'description' => 'Updated.', 'achievement_type' => 'Award',
        ]);

        $this->assertDatabaseHas('achievements', ['id' => $achievement->id, 'title' => 'Updated Title']);
    }

    public function test_alumni_cannot_edit_an_already_approved_achievement(): void
    {
        [$user, $alumni] = $this->alumniUser();
        $achievement = Achievement::factory()->approved()->create(['alumni_id' => $alumni->id]);

        $this->actingAs($user)->get("/dashboard/achievements/{$achievement->id}/edit")->assertForbidden();
    }

    public function test_an_unlinked_account_sees_a_clear_message_and_no_submit_button(): void
    {
        $unlinked = User::factory()->create();

        $response = $this->actingAs($unlinked)->get('/dashboard/achievements');

        $response->assertOk();
        $response->assertSee('No linked alumni record');
        $response->assertSee('Return to Dashboard');
        $response->assertDontSee('Add Achievement');
    }

    public function test_an_unlinked_account_cannot_reach_the_submit_form_or_store(): void
    {
        $unlinked = User::factory()->create();

        $this->actingAs($unlinked)->get('/dashboard/achievements/create')
            ->assertRedirect(route('dashboard.achievements.index'));

        $this->actingAs($unlinked)->post('/dashboard/achievements', [
            'title' => 'Sneaky', 'description' => 'x', 'achievement_type' => 'Award',
        ])->assertForbidden();

        $this->assertDatabaseCount('achievements', 0);
    }

    public function test_an_admin_without_a_linked_alumni_record_is_still_blocked_from_submitting(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/dashboard/achievements')
            ->assertOk()
            ->assertSee('No linked alumni record')
            ->assertSee('Return to Dashboard')
            ->assertDontSee('Add Achievement');

        $this->actingAs($admin)->post('/dashboard/achievements', [
            'title' => 'Admin Attempt', 'description' => 'x', 'achievement_type' => 'Award',
        ])->assertForbidden();
    }

    public function test_a_linked_alumnus_sees_the_submit_button_and_their_submissions_by_status(): void
    {
        [$user, $alumni] = $this->alumniUser();
        Achievement::factory()->create(['alumni_id' => $alumni->id, 'title' => 'Pending One']);
        Achievement::factory()->approved()->create(['alumni_id' => $alumni->id, 'title' => 'Approved One']);
        Achievement::factory()->rejected()->create(['alumni_id' => $alumni->id, 'title' => 'Rejected One']);

        $this->actingAs($user)->get('/dashboard/achievements')
            ->assertOk()
            ->assertSee('Add Achievement')
            ->assertSee('Pending One')
            ->assertSee('Approved One')
            ->assertSee('Rejected One');
    }

    public function test_linked_eligible_alumni_can_view_create_form_and_reach_submission(): void
    {
        [$user, $alumni] = $this->alumniUser();

        $this->actingAs($user)->get('/dashboard/achievements/create')
            ->assertOk()
            ->assertSee('Submit Achievement')
            ->assertSee('Title')
            ->assertSee('Description')
            ->assertSee('Type');

        $this->actingAs($user)->post('/dashboard/achievements', [
            'title' => 'Distinguished Researcher 2026',
            'description' => 'Received premier research recognition.',
            'achievement_type' => 'Award',
            'organization' => 'National Science Foundation',
        ])->assertRedirect(route('dashboard.achievements.index'));

        $this->assertDatabaseHas('achievements', [
            'title' => 'Distinguished Researcher 2026',
            'alumni_id' => $alumni->id,
            'status' => Achievement::STATUS_PENDING,
        ]);
    }

    public function test_unverified_alumni_cannot_access_create_or_submit_achievement(): void
    {
        $user = User::factory()->create();
        $unverifiedAlumni = Alumni::factory()->create([
            'user_id' => $user->id,
            'status' => Alumni::STATUS_PENDING,
        ]);

        $this->actingAs($user)->get('/dashboard/achievements')
            ->assertOk()
            ->assertSee('Alumni verification pending')
            ->assertSee('Return to Dashboard')
            ->assertDontSee('Add Achievement');

        $this->actingAs($user)->get('/dashboard/achievements/create')
            ->assertRedirect(route('dashboard.achievements.index'));

        $this->actingAs($user)->post('/dashboard/achievements', [
            'title' => 'Premature Submission',
            'description' => 'Not verified yet.',
            'achievement_type' => 'Award',
        ])->assertForbidden();

        $this->assertDatabaseCount('achievements', 0);
    }

    public function test_submitting_achievement_cannot_spoof_another_alumni_id(): void
    {
        [$user, $ownAlumni] = $this->alumniUser();
        [$otherUser, $victimAlumni] = $this->alumniUser();

        $this->actingAs($user)->post('/dashboard/achievements', [
            'title' => 'Spoof Attempt',
            'description' => 'Trying to attribute to another alumnus.',
            'achievement_type' => 'Publication',
            'alumni_id' => $victimAlumni->id,
            'status' => 'approved',
        ])->assertRedirect(route('dashboard.achievements.index'));

        $created = Achievement::where('title', 'Spoof Attempt')->firstOrFail();
        $this->assertEquals($ownAlumni->id, $created->alumni_id);
        $this->assertEquals(Achievement::STATUS_PENDING, $created->status);
    }
}
