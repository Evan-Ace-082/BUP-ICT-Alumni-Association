<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Batch;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function userWithRole(string $slug, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->roles()->attach(Role::where('slug', $slug)->firstOrFail());

        return $user;
    }

    // ------------------------------------------------------------------
    // Guest / Alumni cannot manage
    // ------------------------------------------------------------------

    public function test_guest_cannot_access_admin_announcement_management(): void
    {
        $this->get('/admin/announcements')->assertRedirect(route('login'));
        $this->get('/admin/announcements/create')->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_batch_rep_announcement_management(): void
    {
        $this->get('/batch-rep/announcements')->assertRedirect(route('login'));
    }

    public function test_plain_alumni_cannot_access_admin_announcement_management(): void
    {
        $alumni = $this->userWithRole('alumni');

        $this->actingAs($alumni)->get('/admin/announcements')->assertForbidden();
    }

    public function test_plain_alumni_cannot_access_batch_rep_announcement_management(): void
    {
        $alumni = $this->userWithRole('alumni');

        $this->actingAs($alumni)->get('/batch-rep/announcements')->assertForbidden();
    }

    // ------------------------------------------------------------------
    // Batch Representative: own batch only
    // ------------------------------------------------------------------

    public function test_batch_rep_can_create_an_announcement_for_their_own_batch(): void
    {
        $batch = Batch::factory()->create();
        $rep = $this->userWithRole('batch-representative', ['assigned_batch_id' => $batch->id]);

        $response = $this->actingAs($rep)->post('/batch-rep/announcements', [
            'title' => 'Reunion Night',
            'body' => 'Join us for the reunion.',
        ]);

        $response->assertRedirect(route('batch-rep.announcements.index'));
        $this->assertDatabaseHas('announcements', [
            'title' => 'Reunion Night',
            'target_type' => Announcement::TARGET_BATCH,
            'batch_id' => $batch->id,
            'status' => Announcement::STATUS_DRAFT,
        ]);
    }

    public function test_batch_rep_cannot_create_an_all_alumni_announcement(): void
    {
        // The BatchRep Form Request has no target_type field at all —
        // confirms the created record is always forced to 'batch',
        // never 'all', no matter what.
        $batch = Batch::factory()->create();
        $rep = $this->userWithRole('batch-representative', ['assigned_batch_id' => $batch->id]);

        $this->actingAs($rep)->post('/batch-rep/announcements', [
            'title' => 'Trying to go global',
            'body' => 'Body text.',
            'target_type' => 'all', // malicious/extra field, must be ignored
        ]);

        $this->assertDatabaseHas('announcements', [
            'title' => 'Trying to go global',
            'target_type' => Announcement::TARGET_BATCH,
        ]);
        $this->assertDatabaseMissing('announcements', [
            'title' => 'Trying to go global',
            'target_type' => 'all',
        ]);
    }

    public function test_batch_rep_cannot_manipulate_batch_id_via_request(): void
    {
        $ownBatch = Batch::factory()->create();
        $otherBatch = Batch::factory()->create();
        $rep = $this->userWithRole('batch-representative', ['assigned_batch_id' => $ownBatch->id]);

        $this->actingAs($rep)->post('/batch-rep/announcements', [
            'title' => 'IDOR Attempt',
            'body' => 'Body text.',
            'batch_id' => $otherBatch->id, // malicious/extra field, must be ignored
        ]);

        $this->assertDatabaseHas('announcements', [
            'title' => 'IDOR Attempt',
            'batch_id' => $ownBatch->id,
        ]);
        $this->assertDatabaseMissing('announcements', [
            'title' => 'IDOR Attempt',
            'batch_id' => $otherBatch->id,
        ]);
    }

    public function test_batch_rep_cannot_edit_another_batchs_announcement(): void
    {
        $ownBatch = Batch::factory()->create();
        $otherBatch = Batch::factory()->create();
        $rep = $this->userWithRole('batch-representative', ['assigned_batch_id' => $ownBatch->id]);
        $otherAnnouncement = Announcement::factory()->forBatch($otherBatch->id)->create();

        $this->actingAs($rep)
            ->get("/batch-rep/announcements/{$otherAnnouncement->id}/edit")
            ->assertForbidden();

        $this->actingAs($rep)
            ->put("/batch-rep/announcements/{$otherAnnouncement->id}", [
                'title' => 'Hijacked Title',
                'body' => 'Hijacked body.',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('announcements', ['id' => $otherAnnouncement->id, 'title' => 'Hijacked Title']);
    }

    public function test_batch_rep_cannot_publish_another_batchs_announcement(): void
    {
        $ownBatch = Batch::factory()->create();
        $otherBatch = Batch::factory()->create();
        $rep = $this->userWithRole('batch-representative', ['assigned_batch_id' => $ownBatch->id]);
        $otherAnnouncement = Announcement::factory()->forBatch($otherBatch->id)->create();

        $this->actingAs($rep)
            ->patch("/batch-rep/announcements/{$otherAnnouncement->id}/publish")
            ->assertForbidden();

        $this->assertDatabaseHas('announcements', ['id' => $otherAnnouncement->id, 'status' => Announcement::STATUS_DRAFT]);
    }

    public function test_batch_rep_cannot_manage_a_general_all_alumni_announcement(): void
    {
        $batch = Batch::factory()->create();
        $rep = $this->userWithRole('batch-representative', ['assigned_batch_id' => $batch->id]);
        $general = Announcement::factory()->create(); // target_type = all, by default

        $this->actingAs($rep)
            ->get("/batch-rep/announcements/{$general->id}/edit")
            ->assertForbidden();
    }

    // ------------------------------------------------------------------
    // Admin / Super Admin: full access
    // ------------------------------------------------------------------

    public function test_admin_can_create_edit_and_publish_any_announcement(): void
    {
        $batch = Batch::factory()->create();
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->post('/admin/announcements', [
            'title' => 'General Notice',
            'body' => 'Body text.',
            'target_type' => 'all',
        ])->assertRedirect(route('admin.announcements.index'));

        $announcement = Announcement::where('title', 'General Notice')->firstOrFail();

        $this->actingAs($admin)->put("/admin/announcements/{$announcement->id}", [
            'title' => 'Updated Notice',
            'body' => 'Updated body.',
            'target_type' => 'batch',
            'batch_id' => $batch->id,
        ])->assertRedirect(route('admin.announcements.index'));

        $this->assertDatabaseHas('announcements', ['id' => $announcement->id, 'title' => 'Updated Notice', 'batch_id' => $batch->id]);

        $this->actingAs($admin)
            ->patch("/admin/announcements/{$announcement->id}/publish")
            ->assertRedirect(route('admin.announcements.index'));

        $this->assertDatabaseHas('announcements', ['id' => $announcement->id, 'status' => Announcement::STATUS_PUBLISHED]);
    }

    public function test_super_admin_has_the_same_full_access_as_admin(): void
    {
        $superAdmin = $this->userWithRole('super-admin');

        $this->actingAs($superAdmin)
            ->post('/admin/announcements', ['title' => 'From Super Admin', 'body' => 'Body.', 'target_type' => 'all'])
            ->assertRedirect(route('admin.announcements.index'));

        $this->assertDatabaseHas('announcements', ['title' => 'From Super Admin']);
    }

    public function test_batch_target_requires_a_batch_id(): void
    {
        $admin = $this->userWithRole('admin');

        $response = $this->actingAs($admin)->post('/admin/announcements', [
            'title' => 'Missing Batch',
            'body' => 'Body.',
            'target_type' => 'batch',
        ]);

        $response->assertSessionHasErrors('batch_id');
    }

    public function test_admin_can_unpublish_an_announcement(): void
    {
        $admin = $this->userWithRole('admin');
        $announcement = Announcement::factory()->published()->create();

        $this->actingAs($admin)
            ->patch("/admin/announcements/{$announcement->id}/unpublish")
            ->assertRedirect(route('admin.announcements.index'));

        $this->assertDatabaseHas('announcements', ['id' => $announcement->id, 'status' => Announcement::STATUS_DRAFT]);
    }
}
