<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Batch;
use App\Models\Event;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventManagementTest extends TestCase
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

    protected function alumniUser(int $batchId): User
    {
        $user = User::factory()->create();
        Alumni::factory()->active()->create(['user_id' => $user->id, 'batch_id' => $batchId]);

        return $user;
    }

    public function test_guest_can_view_a_published_general_event(): void
    {
        Event::factory()->published()->create(['title' => 'Annual Meetup']);

        $this->get('/events')->assertOk()->assertSee('Annual Meetup');
    }

    public function test_unpublished_events_are_hidden_from_guests(): void
    {
        $event = Event::factory()->create(); // draft

        $this->get('/events')->assertOk()->assertDontSee($event->title);
        $this->get("/events/{$event->id}")->assertNotFound();
    }

    public function test_guest_cannot_view_a_batch_specific_event(): void
    {
        $batch = Batch::factory()->create();
        $event = Event::factory()->forBatch($batch->id)->published()->create();

        $this->get("/events/{$event->id}")->assertForbidden();
    }

    public function test_admin_can_create_edit_publish_and_cancel_an_event(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->post('/admin/events', [
            'title' => 'Career Fair', 'description' => 'Details.', 'event_type' => 'Career', 'event_date' => now()->addWeek()->format('Y-m-d'),
        ])->assertRedirect(route('admin.events.index'));

        $event = Event::where('title', 'Career Fair')->firstOrFail();

        $this->actingAs($admin)->put("/admin/events/{$event->id}", [
            'title' => 'Career Fair 2026', 'description' => 'Updated.', 'event_type' => 'Career', 'event_date' => now()->addWeek()->format('Y-m-d'),
        ]);
        $this->assertDatabaseHas('events', ['id' => $event->id, 'title' => 'Career Fair 2026']);

        $this->actingAs($admin)->patch("/admin/events/{$event->id}/publish");
        $this->assertDatabaseHas('events', ['id' => $event->id, 'status' => 'published']);

        $this->actingAs($admin)->patch("/admin/events/{$event->id}/cancel");
        $this->assertDatabaseHas('events', ['id' => $event->id, 'status' => 'cancelled']);
    }

    public function test_alumni_can_view_a_published_event_for_their_batch(): void
    {
        $batch = Batch::factory()->create();
        $user = $this->alumniUser($batch->id);
        $event = Event::factory()->forBatch($batch->id)->published()->create();

        $this->actingAs($user)->get("/events/{$event->id}")->assertOk();
    }

    public function test_alumni_cannot_view_another_batchs_event(): void
    {
        $ownBatch = Batch::factory()->create();
        $otherBatch = Batch::factory()->create();
        $user = $this->alumniUser($ownBatch->id);
        $event = Event::factory()->forBatch($otherBatch->id)->published()->create();

        $this->actingAs($user)->get("/events/{$event->id}")->assertForbidden();
    }

    public function test_unauthorized_users_cannot_manage_events(): void
    {
        $this->get('/admin/events')->assertRedirect(route('login'));

        $alumni = $this->userWithRole('alumni');
        $this->actingAs($alumni)->get('/admin/events')->assertForbidden();
        $this->actingAs($alumni)->get('/batch-rep/events')->assertForbidden();
    }

    public function test_batch_rep_can_manage_only_their_own_batch_event(): void
    {
        $ownBatch = Batch::factory()->create();
        $otherBatch = Batch::factory()->create();
        $rep = $this->userWithRole('batch-representative', ['assigned_batch_id' => $ownBatch->id]);

        $this->actingAs($rep)->post('/batch-rep/events', [
            'title' => 'Batch Meetup', 'description' => 'Details.', 'event_type' => 'Reunion', 'event_date' => now()->addWeek()->format('Y-m-d'),
        ])->assertRedirect(route('batch-rep.events.index'));

        $this->assertDatabaseHas('events', ['title' => 'Batch Meetup', 'batch_id' => $ownBatch->id]);

        $otherEvent = Event::factory()->forBatch($otherBatch->id)->create();
        $this->actingAs($rep)->get("/batch-rep/events/{$otherEvent->id}/edit")->assertForbidden();
    }

    public function test_batch_rep_cannot_spoof_batch_id_when_creating_an_event(): void
    {
        $ownBatch = Batch::factory()->create();
        $otherBatch = Batch::factory()->create();
        $rep = $this->userWithRole('batch-representative', ['assigned_batch_id' => $ownBatch->id]);

        $this->actingAs($rep)->post('/batch-rep/events', [
            'title' => 'Spoofed Event', 'description' => 'Details.', 'event_type' => 'Reunion',
            'event_date' => now()->addWeek()->format('Y-m-d'), 'batch_id' => $otherBatch->id,
        ]);

        $this->assertDatabaseHas('events', ['title' => 'Spoofed Event', 'batch_id' => $ownBatch->id]);
    }
}
