<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\AuditLog;
use App\Models\Committee;
use App\Models\Election;
use App\Models\ElectionCandidate;
use App\Models\ElectionPosition;
use App\Models\ElectionVote;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElectionFinalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'admin')->firstOrFail());

        return $user;
    }

    protected function voter(): User
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $user->roles()->attach(Role::where('slug', 'alumni')->firstOrFail());
        Alumni::factory()->active()->create(['user_id' => $user->id]);

        return $user;
    }

    public function test_correct_online_winner_is_calculated_and_committee_is_generated_on_finalization(): void
    {
        $admin = $this->admin();

        $election = Election::factory()->create([
            'type' => Election::TYPE_ONLINE,
            'status' => Election::STATUS_ONGOING,
            'term' => '2026-2028',
            'start_time' => now()->subDay(),
            'end_time' => now()->addDay(),
        ]);

        $position = ElectionPosition::factory()->create([
            'election_id' => $election->id,
            'name' => 'President',
            'display_order' => 1,
        ]);

        $candA = ElectionCandidate::factory()->create([
            'election_id' => $election->id,
            'election_position_id' => $position->id,
        ]);
        $candB = ElectionCandidate::factory()->create([
            'election_id' => $election->id,
            'election_position_id' => $position->id,
        ]);

        // Cast 2 votes for candA, 1 vote for candB
        $voter1 = $this->voter();
        $voter2 = $this->voter();
        $voter3 = $this->voter();

        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candA->id, 'voter_id' => $voter1->id]);
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candA->id, 'voter_id' => $voter2->id]);
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candB->id, 'voter_id' => $voter3->id]);

        // Close the election
        $this->actingAs($admin)->patch("/admin/elections/{$election->id}/close")->assertRedirect();
        $this->assertEquals(Election::STATUS_CLOSED, $election->fresh()->status);

        // candA should be marked as winner
        $this->assertTrue($candA->fresh()->is_winner);
        $this->assertFalse($candB->fresh()->is_winner);

        // Finalize election
        $this->actingAs($admin)->patch("/admin/elections/{$election->id}/finalize")->assertRedirect();

        $this->assertEquals(Election::STATUS_FINALIZED, $election->fresh()->status);
        $this->assertNotNull($election->fresh()->finalized_at);

        // Committee generated
        $this->assertDatabaseHas('committees', [
            'election_id' => $election->id,
            'term' => '2026-2028',
            'is_current' => true,
        ]);

        $committee = Committee::where('election_id', $election->id)->firstOrFail();
        $this->assertDatabaseHas('committee_members', [
            'committee_id' => $committee->id,
            'alumni_id' => $candA->alumni_id,
            'position_name' => 'President',
            'display_order' => 1,
        ]);

        // Audit log created
        $this->assertDatabaseHas('audit_logs', [
            'target_id' => $election->id,
            'action' => AuditLog::ACTION_ELECTION_FINALIZED,
        ]);
    }

    public function test_tied_results_block_finalization_until_explicitly_resolved(): void
    {
        $admin = $this->admin();

        $election = Election::factory()->create([
            'type' => Election::TYPE_ONLINE,
            'status' => Election::STATUS_ONGOING,
            'start_time' => now()->subDay(),
            'end_time' => now()->addDay(),
        ]);

        $position = ElectionPosition::factory()->create([
            'election_id' => $election->id,
            'name' => 'General Secretary',
        ]);

        $candA = ElectionCandidate::factory()->create([
            'election_id' => $election->id,
            'election_position_id' => $position->id,
        ]);
        $candB = ElectionCandidate::factory()->create([
            'election_id' => $election->id,
            'election_position_id' => $position->id,
        ]);

        // Tie: 1 vote each
        $voter1 = $this->voter();
        $voter2 = $this->voter();
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candA->id, 'voter_id' => $voter1->id]);
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candB->id, 'voter_id' => $voter2->id]);

        $this->actingAs($admin)->patch("/admin/elections/{$election->id}/close")->assertRedirect();

        // Attempting to finalize while tied fails
        $this->actingAs($admin)->patch("/admin/elections/{$election->id}/finalize")
            ->assertSessionHasErrors('finalization');

        $this->assertFalse($election->fresh()->isFinalized());

        // Admin explicitly resolves tie in favor of candB
        $this->actingAs($admin)->post("/admin/elections/{$election->id}/resolve-tie", [
            'position_id' => $position->id,
            'candidate_id' => $candB->id,
        ])->assertRedirect();

        $this->assertTrue($candB->fresh()->is_winner);
        $this->assertFalse($candA->fresh()->is_winner);

        // Tie resolution audit log recorded
        $this->assertDatabaseHas('audit_logs', [
            'target_id' => $election->id,
            'action' => AuditLog::ACTION_ELECTION_TIE_RESOLVED,
        ]);

        // Now finalization succeeds
        $this->actingAs($admin)->patch("/admin/elections/{$election->id}/finalize")->assertRedirect();
        $this->assertTrue($election->fresh()->isFinalized());
    }

    public function test_physical_election_winner_selection_and_finalization(): void
    {
        $admin = $this->admin();

        $election = Election::factory()->physical()->create([
            'status' => Election::STATUS_CLOSED,
            'term' => '2026-2028',
        ]);

        $position = ElectionPosition::factory()->create([
            'election_id' => $election->id,
            'name' => 'Treasurer',
        ]);

        $candA = ElectionCandidate::factory()->create([
            'election_id' => $election->id,
            'election_position_id' => $position->id,
        ]);
        $candB = ElectionCandidate::factory()->create([
            'election_id' => $election->id,
            'election_position_id' => $position->id,
        ]);

        // Admin selects physical winner manually
        $this->actingAs($admin)->post("/admin/elections/{$election->id}/select-physical-winner", [
            'position_id' => $position->id,
            'candidate_id' => $candA->id,
        ])->assertRedirect();

        $this->assertTrue($candA->fresh()->is_winner);
        $this->assertFalse($candB->fresh()->is_winner);

        $this->assertDatabaseHas('audit_logs', [
            'target_id' => $election->id,
            'action' => AuditLog::ACTION_PHYSICAL_WINNER_SELECTED,
        ]);

        // Finalize physical election generates committee
        $this->actingAs($admin)->patch("/admin/elections/{$election->id}/finalize")->assertRedirect();
        $this->assertTrue($election->fresh()->isFinalized());

        $committee = Committee::where('election_id', $election->id)->firstOrFail();
        $this->assertDatabaseHas('committee_members', [
            'committee_id' => $committee->id,
            'alumni_id' => $candA->alumni_id,
            'position_name' => 'Treasurer',
        ]);
    }

    public function test_guest_can_view_finalized_results_but_not_unfinalized_results(): void
    {
        $finalizedElection = Election::factory()->finalized()->create(['title' => 'Finalized 2026 Election']);
        $draftElection = Election::factory()->create(['title' => 'Draft Election']);

        // Finalized results accessible to guest
        $this->get('/elections/results')->assertOk()->assertSee('Finalized 2026 Election');
        $this->get("/elections/results/{$finalizedElection->id}")->assertOk()->assertSee('Finalized 2026 Election');

        // Unfinalized election results return 404
        $this->get("/elections/results/{$draftElection->id}")->assertNotFound();
    }

    public function test_public_results_do_not_expose_voter_identities(): void
    {
        $election = Election::factory()->finalized()->create();
        $position = ElectionPosition::factory()->create(['election_id' => $election->id]);
        $candidate = ElectionCandidate::factory()->create([
            'election_id' => $election->id,
            'election_position_id' => $position->id,
            'is_winner' => true,
        ]);

        $voter = $this->voter();
        ElectionVote::factory()->create([
            'election_id' => $election->id,
            'election_position_id' => $position->id,
            'election_candidate_id' => $candidate->id,
            'voter_id' => $voter->id,
        ]);

        $response = $this->get("/elections/results/{$election->id}");
        $response->assertOk();
        // Shows candidate
        $response->assertSee($candidate->alumni->name);
        // Does NOT show voter's name or email
        $response->assertDontSee($voter->name);
        $response->assertDontSee($voter->email);
    }
}
