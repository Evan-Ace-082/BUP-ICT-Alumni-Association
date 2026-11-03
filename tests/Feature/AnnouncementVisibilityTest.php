<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Announcement;
use App\Models\Batch;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function alumniUser(int $batchId): User
    {
        $user = User::factory()->create();
        Alumni::factory()->active()->create(['user_id' => $user->id, 'batch_id' => $batchId]);

        return $user;
    }

    // ------------------------------------------------------------------
    // Guest
    // ------------------------------------------------------------------

    public function test_guest_can_see_a_general_announcement_in_the_index(): void
    {
        $announcement = Announcement::factory()->published()->create(['title' => 'General Notice']);

        $this->get('/announcements')->assertOk()->assertSee('General Notice');
    }

    public function test_guest_can_view_a_general_announcements_details(): void
    {
        $announcement = Announcement::factory()->published()->create();

        $this->get("/announcements/{$announcement->id}")->assertOk();
    }

    public function test_guest_can_view_an_explicitly_public_batch_announcement(): void
    {
        $batch = Batch::factory()->create();
        $announcement = Announcement::factory()->forBatch($batch->id)->public()->published()->create();

        $this->get("/announcements/{$announcement->id}")->assertOk();
    }

    public function test_guest_cannot_view_a_private_batch_announcement(): void
    {
        $batch = Batch::factory()->create();
        $announcement = Announcement::factory()->forBatch($batch->id)->published()->create(['is_public' => false]);

        $this->get("/announcements/{$announcement->id}")->assertForbidden();
    }

    public function test_guest_index_never_shows_a_private_batch_announcement(): void
    {
        $batch = Batch::factory()->create();
        Announcement::factory()->forBatch($batch->id)->published()->create(['title' => 'Private Batch Notice', 'is_public' => false]);

        $this->get('/announcements')->assertOk()->assertDontSee('Private Batch Notice');
    }

    public function test_unpublished_announcement_is_not_visible_to_guest(): void
    {
        $announcement = Announcement::factory()->create(); // draft by default

        $this->get("/announcements/{$announcement->id}")->assertNotFound();
        $this->get('/announcements')->assertOk()->assertDontSee($announcement->title);
    }

    // ------------------------------------------------------------------
    // Alumni
    // ------------------------------------------------------------------

    public function test_alumni_sees_general_announcement(): void
    {
        $batch = Batch::factory()->create();
        $user = $this->alumniUser($batch->id);
        Announcement::factory()->published()->create(['title' => 'General For Everyone']);

        $this->actingAs($user)->get('/announcements')->assertOk()->assertSee('General For Everyone');
    }

    public function test_alumni_sees_their_own_batchs_announcement(): void
    {
        $batch = Batch::factory()->create();
        $user = $this->alumniUser($batch->id);
        $announcement = Announcement::factory()->forBatch($batch->id)->published()->create(['title' => 'My Batch News']);

        $this->actingAs($user)->get('/announcements')->assertOk()->assertSee('My Batch News');
        $this->actingAs($user)->get("/announcements/{$announcement->id}")->assertOk();
    }

    public function test_alumni_does_not_see_another_batchs_announcement_in_index(): void
    {
        $ownBatch = Batch::factory()->create();
        $otherBatch = Batch::factory()->create();
        $user = $this->alumniUser($ownBatch->id);
        Announcement::factory()->forBatch($otherBatch->id)->published()->create(['title' => 'Other Batch News']);

        $this->actingAs($user)->get('/announcements')->assertOk()->assertDontSee('Other Batch News');
    }

    public function test_alumni_cannot_open_another_batchs_announcement_by_url(): void
    {
        $ownBatch = Batch::factory()->create();
        $otherBatch = Batch::factory()->create();
        $user = $this->alumniUser($ownBatch->id);
        $announcement = Announcement::factory()->forBatch($otherBatch->id)->published()->create(['body' => 'Secret batch content']);

        $response = $this->actingAs($user)->get("/announcements/{$announcement->id}");

        $response->assertForbidden();
        $response->assertDontSee('Secret batch content');
    }

    public function test_two_different_batches_never_see_each_others_announcements(): void
    {
        $batch7 = Batch::factory()->create(['batch_code' => 'B07']);
        $batch9 = Batch::factory()->create(['batch_code' => 'B09']);
        $userInBatch7 = $this->alumniUser($batch7->id);

        $announcementForBatch9 = Announcement::factory()->forBatch($batch9->id)->published()->create();

        $this->actingAs($userInBatch7)
            ->get("/announcements/{$announcementForBatch9->id}")
            ->assertForbidden();
    }

    // ------------------------------------------------------------------
    // Profile browsing must be completely unaffected (Phase 4 regression)
    // ------------------------------------------------------------------

    public function test_alumni_can_still_browse_and_open_another_batchs_profile(): void
    {
        $ownBatch = Batch::factory()->create();
        $otherBatch = Batch::factory()->create();
        $user = $this->alumniUser($ownBatch->id);
        $otherProfile = Alumni::factory()->create(['batch_id' => $otherBatch->id, 'full_name' => 'Other Batch Person']);

        $this->actingAs($user)->get('/alumni')->assertOk()->assertSee('Other Batch Person');
        $this->actingAs($user)->get("/alumni/{$otherProfile->id}")->assertOk()->assertSee('Other Batch Person');
    }

    public function test_alumni_can_still_filter_the_directory_by_any_batch(): void
    {
        $ownBatch = Batch::factory()->create();
        $otherBatch = Batch::factory()->create();
        $user = $this->alumniUser($ownBatch->id);
        Alumni::factory()->create(['batch_id' => $otherBatch->id, 'full_name' => 'Filterable Person']);

        $this->actingAs($user)
            ->get("/alumni?batch_id={$otherBatch->id}")
            ->assertOk()
            ->assertSee('Filterable Person');
    }

    // ------------------------------------------------------------------
    // Batch Representative visibility
    // ------------------------------------------------------------------

    public function test_batch_rep_sees_general_and_own_batch_announcements_but_not_others(): void
    {
        $ownBatch = Batch::factory()->create();
        $otherBatch = Batch::factory()->create();
        $rep = User::factory()->create(['assigned_batch_id' => $ownBatch->id]);
        $rep->roles()->attach(Role::where('slug', 'batch-representative')->firstOrFail());

        Announcement::factory()->published()->create(['title' => 'General Notice']);
        Announcement::factory()->forBatch($ownBatch->id)->published()->create(['title' => 'Own Batch Notice']);
        Announcement::factory()->forBatch($otherBatch->id)->published()->create(['title' => 'Other Batch Notice']);

        $response = $this->actingAs($rep)->get('/announcements');

        $response->assertOk();
        $response->assertSee('General Notice');
        $response->assertSee('Own Batch Notice');
        $response->assertDontSee('Other Batch Notice');
    }

    // ------------------------------------------------------------------
    // Admin sees everything published
    // ------------------------------------------------------------------

    public function test_admin_sees_every_published_announcement_regardless_of_batch(): void
    {
        $batch = Batch::factory()->create();
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'admin')->firstOrFail());

        Announcement::factory()->forBatch($batch->id)->published()->create(['title' => 'Batch Only Notice']);

        $this->actingAs($admin)->get('/announcements')->assertOk()->assertSee('Batch Only Notice');
    }
}
