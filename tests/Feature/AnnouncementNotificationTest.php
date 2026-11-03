<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Announcement;
use App\Models\Batch;
use App\Models\Role;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AnnouncementNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'admin')->firstOrFail());

        return $admin;
    }

    protected function alumniUser(int $batchId, string $status = 'active'): User
    {
        $user = User::factory()->create();
        Alumni::factory()->create(['user_id' => $user->id, 'batch_id' => $batchId, 'status' => $status]);

        return $user;
    }

    public function test_publishing_a_general_announcement_notifies_all_eligible_alumni(): void
    {
        Notification::fake();

        $batchA = Batch::factory()->create();
        $batchB = Batch::factory()->create();
        $userA = $this->alumniUser($batchA->id);
        $userB = $this->alumniUser($batchB->id);
        $admin = $this->admin();

        $announcement = Announcement::factory()->create(['target_type' => 'all']);

        $this->actingAs($admin)->patch("/admin/announcements/{$announcement->id}/publish");

        Notification::assertSentTo([$userA, $userB], AnnouncementPublished::class);
    }

    public function test_publishing_a_batch_announcement_notifies_only_that_batch(): void
    {
        Notification::fake();

        $targetBatch = Batch::factory()->create();
        $otherBatch = Batch::factory()->create();
        $inTargetBatch = $this->alumniUser($targetBatch->id);
        $inOtherBatch = $this->alumniUser($otherBatch->id);
        $admin = $this->admin();

        $announcement = Announcement::factory()->forBatch($targetBatch->id)->create();

        $this->actingAs($admin)->patch("/admin/announcements/{$announcement->id}/publish");

        Notification::assertSentTo($inTargetBatch, AnnouncementPublished::class);
        Notification::assertNotSentTo($inOtherBatch, AnnouncementPublished::class);
    }

    public function test_unclaimed_alumni_with_no_user_account_receive_nothing(): void
    {
        Notification::fake();

        $batch = Batch::factory()->create();
        Alumni::factory()->create(['batch_id' => $batch->id, 'status' => 'unclaimed', 'user_id' => null]);
        $admin = $this->admin();

        $announcement = Announcement::factory()->forBatch($batch->id)->create();

        $this->actingAs($admin)->patch("/admin/announcements/{$announcement->id}/publish");

        Notification::assertNothingSent();
    }

    public function test_a_notification_database_record_is_actually_created(): void
    {
        // No fake here — verifying the real database channel writes a
        // row, not just that the facade was called.
        $batch = Batch::factory()->create();
        $user = $this->alumniUser($batch->id);
        $admin = $this->admin();

        $announcement = Announcement::factory()->forBatch($batch->id)->create();

        $this->actingAs($admin)->patch("/admin/announcements/{$announcement->id}/publish");

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
            'type' => AnnouncementPublished::class,
        ]);
    }

    public function test_unread_count_reflects_new_notifications(): void
    {
        $batch = Batch::factory()->create();
        $user = $this->alumniUser($batch->id);
        $admin = $this->admin();

        $announcement = Announcement::factory()->forBatch($batch->id)->create();
        $this->actingAs($admin)->patch("/admin/announcements/{$announcement->id}/publish");

        $this->assertEquals(1, $user->fresh()->unreadNotifications()->count());
    }

    public function test_marking_a_notification_read_updates_unread_count(): void
    {
        $batch = Batch::factory()->create();
        $user = $this->alumniUser($batch->id);
        $admin = $this->admin();

        $announcement = Announcement::factory()->forBatch($batch->id)->create();
        $this->actingAs($admin)->patch("/admin/announcements/{$announcement->id}/publish");

        $notification = $user->fresh()->unreadNotifications()->firstOrFail();

        $this->actingAs($user)
            ->post("/dashboard/notifications/{$notification->id}/read")
            ->assertRedirect();

        $this->assertEquals(0, $user->fresh()->unreadNotifications()->count());
        $this->assertEquals(1, $user->fresh()->readNotifications()->count());
    }

    public function test_a_user_cannot_mark_another_users_notification_as_read(): void
    {
        $batch = Batch::factory()->create();
        $recipient = $this->alumniUser($batch->id);
        $intruder = $this->alumniUser($batch->id);
        $admin = $this->admin();

        $announcement = Announcement::factory()->forBatch($batch->id)->create();
        $this->actingAs($admin)->patch("/admin/announcements/{$announcement->id}/publish");

        $notification = $recipient->fresh()->unreadNotifications()->firstOrFail();

        $this->actingAs($intruder)
            ->post("/dashboard/notifications/{$notification->id}/read")
            ->assertNotFound();

        $this->assertEquals(1, $recipient->fresh()->unreadNotifications()->count());
    }

    public function test_republishing_sends_a_fresh_notification_each_time(): void
    {
        // Documented, predictable behavior: this project does not
        // track "already notified" state — every transition into
        // 'published' notifies again.
        $batch = Batch::factory()->create();
        $user = $this->alumniUser($batch->id);
        $admin = $this->admin();

        $announcement = Announcement::factory()->forBatch($batch->id)->create();

        $this->actingAs($admin)->patch("/admin/announcements/{$announcement->id}/publish");
        $this->actingAs($admin)->patch("/admin/announcements/{$announcement->id}/unpublish");
        $this->actingAs($admin)->patch("/admin/announcements/{$announcement->id}/publish");

        $this->assertEquals(2, $user->fresh()->notifications()->count());
    }

    public function test_editing_a_draft_never_sends_a_notification(): void
    {
        Notification::fake();

        $batch = Batch::factory()->create();
        $user = $this->alumniUser($batch->id);
        $admin = $this->admin();

        $announcement = Announcement::factory()->forBatch($batch->id)->create();

        $this->actingAs($admin)->put("/admin/announcements/{$announcement->id}", [
            'title' => 'Edited While Still Draft',
            'body' => 'Body.',
            'target_type' => 'batch',
            'batch_id' => $batch->id,
        ]);

        Notification::assertNothingSent();
    }
}
