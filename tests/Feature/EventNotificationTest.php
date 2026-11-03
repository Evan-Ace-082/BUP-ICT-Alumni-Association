<?php

namespace Tests\Feature;

use App\Mail\EventMail;
use App\Models\Alumni;
use App\Models\Batch;
use App\Models\Event;
use App\Models\Role;
use App\Models\User;
use App\Notifications\EventPublished;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EventNotificationTest extends TestCase
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

    protected function alumniUser(int $batchId): User
    {
        $user = User::factory()->create();
        Alumni::factory()->active()->create(['user_id' => $user->id, 'batch_id' => $batchId]);

        return $user;
    }

    public function test_publishing_an_all_alumni_event_notifies_all_eligible_linked_alumni(): void
    {
        Notification::fake();

        $batchA = Batch::factory()->create();
        $batchB = Batch::factory()->create();
        $userA = $this->alumniUser($batchA->id);
        $userB = $this->alumniUser($batchB->id);
        $event = Event::factory()->create(); // no batch => all alumni

        $this->actingAs($this->admin())->patch("/admin/events/{$event->id}/publish");

        Notification::assertSentTo([$userA, $userB], EventPublished::class);
    }

    public function test_publishing_a_batch_event_notifies_only_that_batch(): void
    {
        Notification::fake();

        $target = Batch::factory()->create();
        $other = Batch::factory()->create();
        $inTarget = $this->alumniUser($target->id);
        $inOther = $this->alumniUser($other->id);
        $event = Event::factory()->forBatch($target->id)->create();

        $this->actingAs($this->admin())->patch("/admin/events/{$event->id}/publish");

        Notification::assertSentTo($inTarget, EventPublished::class);
        Notification::assertNotSentTo($inOther, EventPublished::class);
    }

    public function test_creating_a_draft_event_sends_no_notification(): void
    {
        Notification::fake();

        $batch = Batch::factory()->create();
        $this->alumniUser($batch->id);

        $this->actingAs($this->admin())->post('/admin/events', [
            'title' => 'Draft Only', 'description' => 'Details.', 'event_type' => 'Reunion',
            'event_date' => now()->addWeek()->format('Y-m-d'),
        ]);

        Notification::assertNothingSent();
    }

    public function test_editing_a_draft_event_sends_no_notification(): void
    {
        Notification::fake();

        $batch = Batch::factory()->create();
        $this->alumniUser($batch->id);
        $event = Event::factory()->create();

        $this->actingAs($this->admin())->put("/admin/events/{$event->id}", [
            'title' => 'Edited Draft', 'description' => 'Updated.', 'event_type' => 'Reunion',
            'event_date' => now()->addWeek()->format('Y-m-d'),
        ]);

        Notification::assertNothingSent();
    }

    public function test_editing_an_already_published_event_does_not_resend_the_notification(): void
    {
        $batch = Batch::factory()->create();
        $user = $this->alumniUser($batch->id);
        $admin = $this->admin();
        $event = Event::factory()->create();

        $this->actingAs($admin)->patch("/admin/events/{$event->id}/publish");
        $this->assertEquals(1, $user->fresh()->notifications()->count());

        $this->actingAs($admin)->put("/admin/events/{$event->id}", [
            'title' => 'Edited After Publish', 'description' => 'Updated.', 'event_type' => 'Reunion',
            'event_date' => now()->addWeek()->format('Y-m-d'),
        ]);

        $this->assertEquals(1, $user->fresh()->notifications()->count());
    }

    public function test_cancelling_an_event_sends_no_additional_notification(): void
    {
        $batch = Batch::factory()->create();
        $user = $this->alumniUser($batch->id);
        $admin = $this->admin();
        $event = Event::factory()->create();

        $this->actingAs($admin)->patch("/admin/events/{$event->id}/publish");
        $this->actingAs($admin)->patch("/admin/events/{$event->id}/cancel");

        $this->assertEquals(1, $user->fresh()->notifications()->count());
    }

    public function test_republishing_after_unpublish_sends_a_fresh_notification(): void
    {
        // Defined behavior, consistent with announcements: every
        // transition into 'published' notifies the audience again.
        $batch = Batch::factory()->create();
        $user = $this->alumniUser($batch->id);
        $admin = $this->admin();
        $event = Event::factory()->create();

        $this->actingAs($admin)->patch("/admin/events/{$event->id}/publish");
        $this->actingAs($admin)->patch("/admin/events/{$event->id}/unpublish");
        $this->actingAs($admin)->patch("/admin/events/{$event->id}/publish");

        $this->assertEquals(2, $user->fresh()->notifications()->count());
    }

    public function test_unclaimed_alumni_without_a_user_account_receive_nothing(): void
    {
        Notification::fake();

        $batch = Batch::factory()->create();
        Alumni::factory()->create(['batch_id' => $batch->id, 'status' => 'unclaimed', 'user_id' => null]);
        $event = Event::factory()->forBatch($batch->id)->create();

        $this->actingAs($this->admin())->patch("/admin/events/{$event->id}/publish");

        Notification::assertNothingSent();
    }

    public function test_unauthorized_users_cannot_trigger_event_publication(): void
    {
        Notification::fake();

        $batch = Batch::factory()->create();
        $recipient = $this->alumniUser($batch->id);
        $plainAlumni = $this->alumniUser($batch->id);
        $event = Event::factory()->create();

        $this->actingAs($plainAlumni)->patch("/admin/events/{$event->id}/publish")->assertForbidden();

        $this->assertDatabaseHas('events', ['id' => $event->id, 'status' => 'draft']);
        Notification::assertNothingSent();
    }

    public function test_a_batch_rep_cannot_publish_another_batchs_event(): void
    {
        Notification::fake();

        $own = Batch::factory()->create();
        $other = Batch::factory()->create();
        $rep = User::factory()->create(['assigned_batch_id' => $own->id]);
        $rep->roles()->attach(Role::where('slug', 'batch-representative')->firstOrFail());
        $this->alumniUser($other->id);
        $event = Event::factory()->forBatch($other->id)->create();

        $this->actingAs($rep)->patch("/batch-rep/events/{$event->id}/publish")->assertForbidden();

        Notification::assertNothingSent();
    }

    public function test_the_event_email_contains_the_title_date_type_and_link(): void
    {
        $batch = Batch::factory()->create();
        $event = Event::factory()->forBatch($batch->id)->create([
            'title' => 'Grand Reunion', 'event_type' => 'Reunion', 'event_date' => '2030-05-20',
        ]);

        $html = (new EventMail($event))->render();

        $this->assertStringContainsString('Grand Reunion', $html);
        $this->assertStringContainsString('Reunion', $html);
        $this->assertStringContainsString('May 20, 2030', $html);
        $this->assertStringContainsString(route('events.show', $event), $html);
    }
}
