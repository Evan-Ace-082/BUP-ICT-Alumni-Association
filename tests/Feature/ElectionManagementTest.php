<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\AuditLog;
use App\Models\Election;
use App\Models\ElectionCandidate;
use App\Models\ElectionPosition;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElectionManagementTest extends TestCase
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

    public function test_super_admin_can_access_election_management(): void
    {
        $superAdmin = $this->userWithRole('super-admin');

        $this->actingAs($superAdmin)->get('/admin/elections')->assertOk();
    }

    public function test_admin_can_access_election_management(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->get('/admin/elections')->assertOk();
    }

    public function test_batch_rep_cannot_access_election_management(): void
    {
        $rep = $this->userWithRole('batch-representative');

        $this->actingAs($rep)->get('/admin/elections')->assertForbidden();
    }

    public function test_alumni_cannot_access_election_management(): void
    {
        $alumni = $this->userWithRole('alumni');

        $this->actingAs($alumni)->get('/admin/elections')->assertForbidden();
    }

    public function test_guest_cannot_access_election_management(): void
    {
        $this->get('/admin/elections')->assertRedirect(route('login'));
    }

    public function test_admin_can_create_a_draft_election_with_initial_positions(): void
    {
        $admin = $this->userWithRole('admin');

        $response = $this->actingAs($admin)->post('/admin/elections', [
            'title' => '2026 Executive Committee Election',
            'description' => 'General election for executive body.',
            'type' => Election::TYPE_ONLINE,
            'term' => '2026-2028',
            'start_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_time' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'initial_positions' => ['President', 'General Secretary'],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('elections', [
            'title' => '2026 Executive Committee Election',
            'status' => Election::STATUS_DRAFT,
            'type' => Election::TYPE_ONLINE,
        ]);

        $election = Election::where('title', '2026 Executive Committee Election')->firstOrFail();
        $this->assertDatabaseHas('election_positions', [
            'election_id' => $election->id,
            'name' => 'President',
        ]);
        $this->assertDatabaseHas('election_positions', [
            'election_id' => $election->id,
            'name' => 'General Secretary',
        ]);

        // Audit log verified
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'target_type' => Election::class,
            'target_id' => $election->id,
            'action' => AuditLog::ACTION_ELECTION_CREATED,
        ]);
    }

    public function test_admin_can_add_custom_position_as_string_and_duplicates_are_blocked(): void
    {
        $admin = $this->userWithRole('admin');
        $election = Election::factory()->create();

        $this->actingAs($admin)->post("/admin/elections/{$election->id}/positions", [
            'name' => 'Logistics Coordinator',
            'display_order' => 5,
        ])->assertRedirect();

        $this->assertDatabaseHas('election_positions', [
            'election_id' => $election->id,
            'name' => 'Logistics Coordinator',
            'display_order' => 5,
        ]);

        // Duplicate position in the same election is rejected
        $this->actingAs($admin)->post("/admin/elections/{$election->id}/positions", [
            'name' => 'Logistics Coordinator',
            'display_order' => 6,
        ])->assertSessionHasErrors('name');
    }

    public function test_admin_can_add_valid_candidate_linked_to_alumni(): void
    {
        $admin = $this->userWithRole('admin');
        $election = Election::factory()->create();
        $position = ElectionPosition::factory()->create(['election_id' => $election->id]);
        $alumni = Alumni::factory()->create(['full_name' => 'Tanvir Ahmed']);

        $this->actingAs($admin)->post("/admin/elections/{$election->id}/candidates", [
            'election_position_id' => $position->id,
            'alumni_id' => $alumni->id,
            'statement' => 'Dedicated to the community.',
        ])->assertRedirect();

        $this->assertDatabaseHas('election_candidates', [
            'election_id' => $election->id,
            'election_position_id' => $position->id,
            'alumni_id' => $alumni->id,
            'statement' => 'Dedicated to the community.',
        ]);

        // Same alumni cannot run twice in the same election
        $this->actingAs($admin)->post("/admin/elections/{$election->id}/candidates", [
            'election_position_id' => $position->id,
            'alumni_id' => $alumni->id,
            'statement' => 'Duplicate attempt.',
        ])->assertSessionHasErrors('alumni_id');
    }

    public function test_candidate_cannot_be_assigned_to_position_from_different_election(): void
    {
        $admin = $this->userWithRole('admin');
        $election1 = Election::factory()->create();
        $election2 = Election::factory()->create();
        $position2 = ElectionPosition::factory()->create(['election_id' => $election2->id]);
        $alumni = Alumni::factory()->create();

        $this->actingAs($admin)->post("/admin/elections/{$election1->id}/candidates", [
            'election_position_id' => $position2->id,
            'alumni_id' => $alumni->id,
        ])->assertSessionHasErrors('election_position_id');
    }

    public function test_valid_lifecycle_transition_from_draft_to_ongoing_to_closed(): void
    {
        $admin = $this->userWithRole('admin');
        $election = Election::factory()->create(['status' => Election::STATUS_DRAFT]);
        $position = ElectionPosition::factory()->create(['election_id' => $election->id]);
        ElectionCandidate::factory()->create([
            'election_id' => $election->id,
            'election_position_id' => $position->id,
        ]);

        // Start election -> ongoing
        $this->actingAs($admin)->patch("/admin/elections/{$election->id}/start")->assertRedirect();
        $this->assertEquals(Election::STATUS_ONGOING, $election->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'target_id' => $election->id,
            'action' => AuditLog::ACTION_ELECTION_OPENED,
        ]);

        // Close election -> closed
        $this->actingAs($admin)->patch("/admin/elections/{$election->id}/close")->assertRedirect();
        $this->assertEquals(Election::STATUS_CLOSED, $election->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'target_id' => $election->id,
            'action' => AuditLog::ACTION_ELECTION_CLOSED,
        ]);
    }

    public function test_starting_election_without_positions_or_candidates_fails(): void
    {
        $admin = $this->userWithRole('admin');
        $emptyElection = Election::factory()->create(['status' => Election::STATUS_DRAFT]);

        $this->actingAs($admin)->patch("/admin/elections/{$emptyElection->id}/start")
            ->assertSessionHasErrors('positions');
    }

    public function test_finalized_election_cannot_be_edited_or_deleted(): void
    {
        $admin = $this->userWithRole('admin');
        $election = Election::factory()->finalized()->create([
            'title' => 'Original Finalized Title',
        ]);

        // Admin can view the edit page, which displays the clear read-only restriction banner
        $this->actingAs($admin)->get("/admin/elections/{$election->id}/edit")
            ->assertOk()
            ->assertSee('Certified &amp; Finalized Election', false)
            ->assertSee('disabled', false);

        // Modifying a finalized election is blocked with a descriptive flash error
        $this->actingAs($admin)->put("/admin/elections/{$election->id}", [
            'title' => 'Attempted Title Update',
            'type' => Election::TYPE_ONLINE,
            'term' => '2026-2028',
            'start_time' => now(),
            'end_time' => now()->addDay(),
        ])->assertRedirect(route('admin.elections.edit', $election))
            ->assertSessionHas('error');

        $this->assertEquals('Original Finalized Title', $election->fresh()->title);

        // Deleting a finalized election is blocked to preserve certified results
        $this->actingAs($admin)->delete("/admin/elections/{$election->id}")
            ->assertRedirect(route('admin.elections.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('elections', [
            'id' => $election->id,
            'title' => 'Original Finalized Title',
        ]);
    }

    public function test_all_three_action_buttons_are_rendered_for_draft_elections(): void
    {
        $admin = $this->userWithRole('admin');
        $draft = Election::factory()->create(['status' => Election::STATUS_DRAFT, 'title' => 'Draft Action Test']);

        $response = $this->actingAs($admin)->get('/admin/elections');
        $response->assertOk();

        // Check for Manage, Edit, and Delete action buttons for this election
        $response->assertSee(route('admin.elections.show', $draft));
        $response->assertSee(route('admin.elections.edit', $draft));
        $response->assertSee('action="' . route('admin.elections.destroy', $draft) . '"', false);
        $response->assertSee('Manage');
        $response->assertSee('Edit');
        $response->assertSee('Delete');
    }

    public function test_all_three_action_buttons_are_rendered_for_ongoing_elections(): void
    {
        $admin = $this->userWithRole('admin');
        $ongoing = Election::factory()->create(['status' => Election::STATUS_ONGOING, 'title' => 'Ongoing Action Test']);

        $response = $this->actingAs($admin)->get('/admin/elections');
        $response->assertOk();

        // Check for Manage, Edit, and Delete action buttons for this election
        $response->assertSee(route('admin.elections.show', $ongoing));
        $response->assertSee(route('admin.elections.edit', $ongoing));
        $response->assertSee('action="' . route('admin.elections.destroy', $ongoing) . '"', false);
    }

    public function test_all_three_action_buttons_are_rendered_for_closed_elections(): void
    {
        $admin = $this->userWithRole('admin');
        $closed = Election::factory()->create(['status' => Election::STATUS_CLOSED, 'title' => 'Closed Action Test']);

        $response = $this->actingAs($admin)->get('/admin/elections');
        $response->assertOk();

        // Check for Manage, Edit, and Delete action buttons for this election
        $response->assertSee(route('admin.elections.show', $closed));
        $response->assertSee(route('admin.elections.edit', $closed));
        $response->assertSee('action="' . route('admin.elections.destroy', $closed) . '"', false);
    }

    public function test_all_three_action_buttons_are_rendered_for_finalized_elections(): void
    {
        $admin = $this->userWithRole('admin');
        $finalized = Election::factory()->finalized()->create(['title' => 'Finalized Action Test']);

        $response = $this->actingAs($admin)->get('/admin/elections');
        $response->assertOk();

        // Check for Manage, Edit, and Delete action buttons for this election
        $response->assertSee(route('admin.elections.show', $finalized));
        $response->assertSee(route('admin.elections.edit', $finalized));
        $response->assertSee('action="' . route('admin.elections.destroy', $finalized) . '"', false);
    }

    public function test_unauthorized_users_cannot_edit_or_delete_elections(): void
    {
        $election = Election::factory()->create(['status' => Election::STATUS_DRAFT]);

        // Guest
        $this->get("/admin/elections/{$election->id}/edit")->assertRedirect(route('login'));
        $this->put("/admin/elections/{$election->id}", ['title' => 'Hacked'])->assertRedirect(route('login'));
        $this->delete("/admin/elections/{$election->id}")->assertRedirect(route('login'));

        // Alumni
        $alumni = $this->userWithRole('alumni');
        $this->actingAs($alumni)->get("/admin/elections/{$election->id}/edit")->assertForbidden();
        $this->actingAs($alumni)->put("/admin/elections/{$election->id}", ['title' => 'Hacked'])->assertForbidden();
        $this->actingAs($alumni)->delete("/admin/elections/{$election->id}")->assertForbidden();

        // Batch Representative
        $rep = $this->userWithRole('batch-representative');
        $this->actingAs($rep)->get("/admin/elections/{$election->id}/edit")->assertForbidden();
        $this->actingAs($rep)->put("/admin/elections/{$election->id}", ['title' => 'Hacked'])->assertForbidden();
        $this->actingAs($rep)->delete("/admin/elections/{$election->id}")->assertForbidden();
    }

    public function test_deletion_protections_work_correctly_for_elections_containing_votes(): void
    {
        $admin = $this->userWithRole('admin');
        $election = Election::factory()->create(['status' => Election::STATUS_CLOSED]);
        $position = ElectionPosition::factory()->create(['election_id' => $election->id]);
        $candidate = ElectionCandidate::factory()->create([
            'election_id' => $election->id,
            'election_position_id' => $position->id,
        ]);
        $voter = $this->userWithRole('alumni');

        \App\Models\ElectionVote::create([
            'election_id' => $election->id,
            'election_position_id' => $position->id,
            'election_candidate_id' => $candidate->id,
            'voter_id' => $voter->id,
        ]);

        $this->assertEquals(1, $election->votes()->count());

        // Attempting to delete must be blocked to protect votes
        $this->actingAs($admin)->delete("/admin/elections/{$election->id}")
            ->assertRedirect(route('admin.elections.index'))
            ->assertSessionHas('error');

        // Confirm election and votes remain intact
        $this->assertDatabaseHas('elections', ['id' => $election->id]);
        $this->assertDatabaseHas('election_votes', [
            'election_id' => $election->id,
            'voter_id' => $voter->id,
        ]);
    }

    public function test_draft_election_with_zero_votes_and_no_committee_can_be_deleted(): void
    {
        $admin = $this->userWithRole('admin');
        $election = Election::factory()->create([
            'status' => Election::STATUS_DRAFT,
            'title' => 'Unused Draft Election',
        ]);

        $this->actingAs($admin)->delete("/admin/elections/{$election->id}")
            ->assertRedirect(route('admin.elections.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('elections', ['id' => $election->id]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'target_type' => Election::class,
            'target_id' => $election->id,
            'action' => AuditLog::ACTION_ELECTION_DELETED,
        ]);
    }

    public function test_closed_election_with_zero_votes_and_no_committee_can_be_deleted(): void
    {
        $admin = $this->userWithRole('admin');
        $election = Election::factory()->create([
            'status' => Election::STATUS_CLOSED,
            'title' => 'Unused Closed Election',
        ]);

        $this->actingAs($admin)->delete("/admin/elections/{$election->id}")
            ->assertRedirect(route('admin.elections.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('elections', ['id' => $election->id]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'target_type' => Election::class,
            'target_id' => $election->id,
            'action' => AuditLog::ACTION_ELECTION_DELETED,
        ]);
    }

    public function test_election_linked_to_a_committee_cannot_be_deleted(): void
    {
        $admin = $this->userWithRole('admin');
        $election = Election::factory()->create(['status' => Election::STATUS_CLOSED]);
        \App\Models\Committee::factory()->create([
            'election_id' => $election->id,
            'term' => '2026-2028',
        ]);

        $this->actingAs($admin)->delete("/admin/elections/{$election->id}")
            ->assertRedirect(route('admin.elections.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('elections', ['id' => $election->id]);
    }

    public function test_ongoing_election_cannot_be_deleted_or_archived(): void
    {
        $admin = $this->userWithRole('admin');
        $election = Election::factory()->create(['status' => Election::STATUS_ONGOING]);

        // Attempt deletion
        $this->actingAs($admin)->delete("/admin/elections/{$election->id}")
            ->assertRedirect(route('admin.elections.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('elections', ['id' => $election->id]);

        // Attempt archival
        $this->actingAs($admin)->post("/admin/elections/{$election->id}/archive")
            ->assertRedirect(route('admin.elections.index'))
            ->assertSessionHas('error');

        $this->assertFalse($election->fresh()->isArchived());
    }

    public function test_authorized_admins_can_archive_eligible_inactive_elections(): void
    {
        $admin = $this->userWithRole('admin');
        $election = Election::factory()->create([
            'status' => Election::STATUS_CLOSED,
            'title' => 'Archivable Closed Election',
            'is_archived' => false,
        ]);

        $response = $this->actingAs($admin)->post("/admin/elections/{$election->id}/archive");
        $response->assertRedirect(route('admin.elections.index'));
        $response->assertSessionHas('success');

        $this->assertTrue($election->fresh()->isArchived());
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'target_type' => Election::class,
            'target_id' => $election->id,
            'action' => AuditLog::ACTION_ELECTION_ARCHIVED,
        ]);
    }

    public function test_archiving_preserves_votes_and_election_results(): void
    {
        $admin = $this->userWithRole('admin');
        $election = Election::factory()->create(['status' => Election::STATUS_CLOSED]);
        $position = ElectionPosition::factory()->create(['election_id' => $election->id]);
        $candidate = ElectionCandidate::factory()->create([
            'election_id' => $election->id,
            'election_position_id' => $position->id,
        ]);
        $voter = $this->userWithRole('alumni');

        \App\Models\ElectionVote::create([
            'election_id' => $election->id,
            'election_position_id' => $position->id,
            'election_candidate_id' => $candidate->id,
            'voter_id' => $voter->id,
        ]);

        // Archive the election
        $this->actingAs($admin)->post("/admin/elections/{$election->id}/archive")
            ->assertRedirect(route('admin.elections.index'))
            ->assertSessionHas('success');

        $this->assertTrue($election->fresh()->isArchived());

        // Votes and candidates remain intact
        $this->assertDatabaseHas('election_votes', [
            'election_id' => $election->id,
            'voter_id' => $voter->id,
        ]);
        $this->assertDatabaseHas('election_candidates', [
            'id' => $candidate->id,
        ]);
        $this->assertEquals(1, $election->fresh()->votes()->count());
    }

    public function test_authorized_admins_can_unarchive_an_election(): void
    {
        $admin = $this->userWithRole('admin');
        $election = Election::factory()->create([
            'status' => Election::STATUS_CLOSED,
            'title' => 'Archived Test Election',
            'is_archived' => true,
        ]);

        $response = $this->actingAs($admin)->post("/admin/elections/{$election->id}/unarchive");
        $response->assertRedirect(route('admin.elections.index'));
        $response->assertSessionHas('success');

        $fresh = $election->fresh();
        $this->assertFalse($fresh->isArchived());
        $this->assertEquals(Election::STATUS_CLOSED, $fresh->status);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'target_type' => Election::class,
            'target_id' => $election->id,
            'action' => AuditLog::ACTION_ELECTION_UNARCHIVED,
        ]);
    }

    public function test_unauthorized_users_cannot_delete_archive_or_unarchive_elections(): void
    {
        $election = Election::factory()->create(['status' => Election::STATUS_CLOSED]);

        // Guest
        $this->delete("/admin/elections/{$election->id}")->assertRedirect(route('login'));
        $this->post("/admin/elections/{$election->id}/archive")->assertRedirect(route('login'));
        $this->post("/admin/elections/{$election->id}/unarchive")->assertRedirect(route('login'));

        // Alumni
        $alumni = $this->userWithRole('alumni');
        $this->actingAs($alumni)->delete("/admin/elections/{$election->id}")->assertForbidden();
        $this->actingAs($alumni)->post("/admin/elections/{$election->id}/archive")->assertForbidden();
        $this->actingAs($alumni)->post("/admin/elections/{$election->id}/unarchive")->assertForbidden();

        // Batch Representative
        $rep = $this->userWithRole('batch-representative');
        $this->actingAs($rep)->delete("/admin/elections/{$election->id}")->assertForbidden();
        $this->actingAs($rep)->post("/admin/elections/{$election->id}/archive")->assertForbidden();
        $this->actingAs($rep)->post("/admin/elections/{$election->id}/unarchive")->assertForbidden();
    }

    public function test_all_active_and_archived_filters_return_the_correct_records(): void
    {
        $admin = $this->userWithRole('admin');
        $activeElection = Election::factory()->create([
            'title' => 'Alpha Active Election',
            'is_archived' => false,
        ]);
        $archivedElection = Election::factory()->create([
            'title' => 'Beta Archived Election',
            'is_archived' => true,
        ]);

        // Active filter (default)
        $responseActive = $this->actingAs($admin)->get('/admin/elections');
        $responseActive->assertOk();
        $responseActive->assertSee('Alpha Active Election');
        $responseActive->assertDontSee('Beta Archived Election');

        // Archived filter
        $responseArchived = $this->actingAs($admin)->get('/admin/elections?filter=archived');
        $responseArchived->assertOk();
        $responseArchived->assertSee('Beta Archived Election');
        $responseArchived->assertDontSee('Alpha Active Election');

        // All filter
        $responseAll = $this->actingAs($admin)->get('/admin/elections?filter=all');
        $responseAll->assertOk();
        $responseAll->assertSee('Alpha Active Election');
        $responseAll->assertSee('Beta Archived Election');
    }

    public function test_admin_cannot_add_inactive_alumni_as_candidate(): void
    {
        $admin = $this->userWithRole('admin');
        $election = Election::factory()->create();
        $position = ElectionPosition::factory()->create(['election_id' => $election->id]);
        $inactiveAlumni = Alumni::factory()->create(['status' => Alumni::STATUS_INACTIVE]);

        $this->actingAs($admin)->post("/admin/elections/{$election->id}/candidates", [
            'election_position_id' => $position->id,
            'alumni_id' => $inactiveAlumni->id,
            'statement' => 'Should be rejected',
        ])->assertSessionHasErrors('alumni_id');
    }

    public function test_admin_can_update_candidate_while_election_is_draft(): void
    {
        $admin = $this->userWithRole('admin');
        $election = Election::factory()->create(['status' => Election::STATUS_DRAFT]);
        $pos1 = ElectionPosition::factory()->create(['election_id' => $election->id, 'name' => 'President']);
        $pos2 = ElectionPosition::factory()->create(['election_id' => $election->id, 'name' => 'Vice President']);
        $candidate = ElectionCandidate::factory()->create([
            'election_id' => $election->id,
            'election_position_id' => $pos1->id,
            'statement' => 'Old statement',
        ]);

        $response = $this->actingAs($admin)->put("/admin/elections/{$election->id}/candidates/{$candidate->id}", [
            'election_position_id' => $pos2->id,
            'statement' => 'New manifesto statement',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('election_candidates', [
            'id' => $candidate->id,
            'election_position_id' => $pos2->id,
            'statement' => 'New manifesto statement',
        ]);
    }

    public function test_admin_can_remove_candidate_while_draft(): void
    {
        $admin = $this->userWithRole('admin');
        $election = Election::factory()->create(['status' => Election::STATUS_DRAFT]);
        $position = ElectionPosition::factory()->create(['election_id' => $election->id]);
        $candidate = ElectionCandidate::factory()->create([
            'election_id' => $election->id,
            'election_position_id' => $position->id,
        ]);

        $this->actingAs($admin)->delete("/admin/elections/{$election->id}/candidates/{$candidate->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('election_candidates', [
            'id' => $candidate->id,
        ]);
    }

    public function test_candidate_cannot_be_updated_or_removed_once_election_is_ongoing(): void
    {
        $admin = $this->userWithRole('admin');
        $election = Election::factory()->create(['status' => Election::STATUS_ONGOING]);
        $position = ElectionPosition::factory()->create(['election_id' => $election->id]);
        $candidate = ElectionCandidate::factory()->create([
            'election_id' => $election->id,
            'election_position_id' => $position->id,
        ]);

        $this->actingAs($admin)->put("/admin/elections/{$election->id}/candidates/{$candidate->id}", [
            'election_position_id' => $position->id,
            'statement' => 'Attempted change',
        ])->assertForbidden();

        $this->actingAs($admin)->delete("/admin/elections/{$election->id}/candidates/{$candidate->id}")
            ->assertForbidden();
    }

    public function test_starting_election_fails_if_any_position_lacks_candidates(): void
    {
        $admin = $this->userWithRole('admin');
        $election = Election::factory()->create(['status' => Election::STATUS_DRAFT]);
        $pos1 = ElectionPosition::factory()->create(['election_id' => $election->id, 'name' => 'President']);
        $pos2 = ElectionPosition::factory()->create(['election_id' => $election->id, 'name' => 'Secretary']);

        // Only pos1 has a candidate
        ElectionCandidate::factory()->create([
            'election_id' => $election->id,
            'election_position_id' => $pos1->id,
        ]);

        $this->actingAs($admin)->patch("/admin/elections/{$election->id}/start")
            ->assertSessionHasErrors('candidates');

        $this->assertEquals(Election::STATUS_DRAFT, $election->fresh()->status);
    }
}
