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
use App\Services\ElectionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ElectionTieBreakingTest extends TestCase
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

    protected function createOnlineClosedElection(string $positionName = 'President'): array
    {
        $election = Election::factory()->create([
            'type' => Election::TYPE_ONLINE,
            'status' => Election::STATUS_CLOSED,
            'term' => '2026-2028',
            'start_time' => now()->subDays(2),
            'end_time' => now()->subHour(),
        ]);

        $position = ElectionPosition::factory()->create([
            'election_id' => $election->id,
            'name' => $positionName,
            'display_order' => 1,
        ]);

        return [$election, $position];
    }

    public function test_unique_highest_vote_count_produces_the_correct_winner(): void
    {
        $admin = $this->admin();
        [$election, $position] = $this->createOnlineClosedElection();

        $candA = ElectionCandidate::factory()->create([
            'election_id' => $election->id,
            'election_position_id' => $position->id,
        ]);
        $candB = ElectionCandidate::factory()->create([
            'election_id' => $election->id,
            'election_position_id' => $position->id,
        ]);

        $voter1 = $this->voter();
        $voter2 = $this->voter();
        $voter3 = $this->voter();

        // 2 votes for candA, 1 vote for candB
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candA->id, 'voter_id' => $voter1->id]);
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candA->id, 'voter_id' => $voter2->id]);
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candB->id, 'voter_id' => $voter3->id]);

        $service = app(ElectionService::class);
        $service->determineOnlineWinners($election);

        $this->assertTrue($candA->fresh()->is_winner);
        $this->assertFalse($candB->fresh()->is_winner);

        $results = $service->getOnlineResults($election);
        $this->assertFalse($results['has_ties']);
        $this->assertFalse($results['results'][0]['is_tied']);
        $this->assertEquals($candA->id, $results['results'][0]['winner']->id);

        $response = $this->actingAs($admin)->get(route('admin.elections.show', $election));
        $response->assertOk();
        $response->assertSee('Winner');
        $response->assertDontSee('TIED — Resolution Required');
    }

    public function test_two_candidates_with_equal_highest_votes_produce_tied_with_no_winner_badge(): void
    {
        $admin = $this->admin();
        [$election, $position] = $this->createOnlineClosedElection();

        $candA = ElectionCandidate::factory()->create([
            'election_id' => $election->id,
            'election_position_id' => $position->id,
        ]);
        $candB = ElectionCandidate::factory()->create([
            'election_id' => $election->id,
            'election_position_id' => $position->id,
        ]);

        $voter1 = $this->voter();
        $voter2 = $this->voter();

        // 1 vote for candA, 1 vote for candB
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candA->id, 'voter_id' => $voter1->id]);
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candB->id, 'voter_id' => $voter2->id]);

        $service = app(ElectionService::class);
        $service->determineOnlineWinners($election);

        // Neither candidate is automatically winner
        $this->assertFalse($candA->fresh()->is_winner);
        $this->assertFalse($candB->fresh()->is_winner);

        $results = $service->getOnlineResults($election);
        $this->assertTrue($results['has_ties']);
        $this->assertTrue($results['results'][0]['is_tied']);
        $this->assertFalse($results['results'][0]['is_resolved_tie']);
        $this->assertNull($results['results'][0]['winner']);
        $this->assertCount(2, $results['results'][0]['leaders']);

        $response = $this->actingAs($admin)->get(route('admin.elections.show', $election));
        $response->assertOk();
        $response->assertSee('TIED — Resolution Required');
        $response->assertSee('Select to Break Tie');
        // Neither candidate should have a Winner badge
        $response->assertDontSee('<span class="badge bg-success ms-1">Winner</span>', false);
    }

    public function test_three_candidates_tied_for_the_highest_votes_are_handled_correctly(): void
    {
        $admin = $this->admin();
        [$election, $position] = $this->createOnlineClosedElection();

        $candA = ElectionCandidate::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id]);
        $candB = ElectionCandidate::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id]);
        $candC = ElectionCandidate::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id]);
        $candD = ElectionCandidate::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id]);

        $v1 = $this->voter();
        $v2 = $this->voter();
        $v3 = $this->voter();
        $v4 = $this->voter();
        $v5 = $this->voter();
        $v6 = $this->voter();
        $v7 = $this->voter();

        // candA: 2 votes, candB: 2 votes, candC: 2 votes, candD: 1 vote
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candA->id, 'voter_id' => $v1->id]);
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candA->id, 'voter_id' => $v2->id]);
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candB->id, 'voter_id' => $v3->id]);
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candB->id, 'voter_id' => $v4->id]);
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candC->id, 'voter_id' => $v5->id]);
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candC->id, 'voter_id' => $v6->id]);
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candD->id, 'voter_id' => $v7->id]);

        $service = app(ElectionService::class);
        $service->determineOnlineWinners($election);

        $this->assertFalse($candA->fresh()->is_winner);
        $this->assertFalse($candB->fresh()->is_winner);
        $this->assertFalse($candC->fresh()->is_winner);
        $this->assertFalse($candD->fresh()->is_winner);

        $results = $service->getOnlineResults($election);
        $this->assertTrue($results['has_ties']);
        $this->assertTrue($results['results'][0]['is_tied']);
        $this->assertCount(3, $results['results'][0]['leaders']);
        $this->assertEquals(2, $results['results'][0]['max_votes']);
        $this->assertNull($results['results'][0]['winner']);
    }

    public function test_lower_vote_candidate_cannot_be_selected_to_break_the_tie(): void
    {
        $admin = $this->admin();
        [$election, $position] = $this->createOnlineClosedElection();

        $candA = ElectionCandidate::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id]);
        $candB = ElectionCandidate::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id]);
        $candC = ElectionCandidate::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id]);

        $v1 = $this->voter();
        $v2 = $this->voter();
        $v3 = $this->voter();
        $v4 = $this->voter();
        $v5 = $this->voter();

        // candA: 2 votes, candB: 2 votes, candC: 1 vote
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candA->id, 'voter_id' => $v1->id]);
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candA->id, 'voter_id' => $v2->id]);
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candB->id, 'voter_id' => $v3->id]);
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candB->id, 'voter_id' => $v4->id]);
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candC->id, 'voter_id' => $v5->id]);

        // Attempting to resolve tie by selecting lower-vote candC
        $response = $this->actingAs($admin)->post(route('admin.elections.resolve-tie', $election), [
            'position_id' => $position->id,
            'candidate_id' => $candC->id,
            'resolution_method' => 'committee_consensus',
            'resolution_reason' => 'Attempting arbitrary choice',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Verify candC was NOT marked as winner and position remains unresolved
        $this->assertFalse($candC->fresh()->is_winner);
        $this->assertFalse($position->fresh()->isTieResolved());
    }

    public function test_unauthorized_users_cannot_resolve_ties(): void
    {
        [$election, $position] = $this->createOnlineClosedElection();

        $candA = ElectionCandidate::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id]);
        $candB = ElectionCandidate::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id]);

        $v1 = $this->voter();
        $v2 = $this->voter();
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candA->id, 'voter_id' => $v1->id]);
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candB->id, 'voter_id' => $v2->id]);

        // 1. Guest request redirected to login
        $response = $this->post(route('admin.elections.resolve-tie', $election), [
            'position_id' => $position->id,
            'candidate_id' => $candA->id,
        ]);
        $response->assertRedirect(route('login'));

        // 2. Regular alumni user gets 403 Forbidden
        $response = $this->actingAs($v1)->post(route('admin.elections.resolve-tie', $election), [
            'position_id' => $position->id,
            'candidate_id' => $candA->id,
        ]);
        $response->assertForbidden();

        $this->assertFalse($position->fresh()->isTieResolved());
        $this->assertFalse($candA->fresh()->is_winner);
    }

    public function test_tie_resolution_action_does_not_modify_original_votes(): void
    {
        $admin = $this->admin();
        [$election, $position] = $this->createOnlineClosedElection();

        $candA = ElectionCandidate::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id]);
        $candB = ElectionCandidate::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id]);

        $v1 = $this->voter();
        $v2 = $this->voter();
        $v3 = $this->voter();
        $v4 = $this->voter();

        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candA->id, 'voter_id' => $v1->id]);
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candA->id, 'voter_id' => $v2->id]);
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candB->id, 'voter_id' => $v3->id]);
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candB->id, 'voter_id' => $v4->id]);

        $initialVotesCount = ElectionVote::count();
        $this->assertEquals(4, $initialVotesCount);

        // Resolve tie for candA
        $response = $this->actingAs($admin)->post(route('admin.elections.resolve-tie', $election), [
            'position_id' => $position->id,
            'candidate_id' => $candA->id,
            'resolution_method' => 'lottery_draw',
            'resolution_reason' => 'Conducted official lottery draw witnessed by EC members.',
            'confirm_resolution' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // 1. Original votes MUST remain completely unchanged
        $this->assertEquals($initialVotesCount, ElectionVote::count());
        $this->assertEquals(2, ElectionVote::where('election_candidate_id', $candA->id)->count());
        $this->assertEquals(2, ElectionVote::where('election_candidate_id', $candB->id)->count());

        // 2. Position metadata recorded
        $position->refresh();
        $this->assertTrue($position->isTieResolved());
        $this->assertEquals('lottery_draw', $position->tie_resolution_method);
        $this->assertEquals('Conducted official lottery draw witnessed by EC members.', $position->tie_resolution_reason);
        $this->assertEquals($admin->id, $position->tie_resolved_by);
        $this->assertNotNull($position->tie_resolved_at);

        // 3. Winner set
        $this->assertTrue($candA->fresh()->is_winner);
        $this->assertFalse($candB->fresh()->is_winner);

        // 4. AuditLog recorded
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'target_type' => Election::class,
            'target_id' => $election->id,
            'action' => AuditLog::ACTION_ELECTION_TIE_RESOLVED,
        ]);

        // 5. Finalize election now succeeds
        $finalizeResponse = $this->actingAs($admin)->patch(route('admin.elections.finalize', $election));
        $finalizeResponse->assertRedirect();
        $this->assertEquals(Election::STATUS_FINALIZED, $election->fresh()->status);
    }

    public function test_a_runoff_that_remains_tied_does_not_produce_an_arbitrary_winner(): void
    {
        [$election, $position] = $this->createOnlineClosedElection();

        $candA = ElectionCandidate::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id]);
        $candB = ElectionCandidate::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id]);

        // Simulate runoff where both receive equal votes (3 votes each)
        for ($i = 0; $i < 3; $i++) {
            $va = $this->voter();
            ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candA->id, 'voter_id' => $va->id]);
            $vb = $this->voter();
            ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $position->id, 'election_candidate_id' => $candB->id, 'voter_id' => $vb->id]);
        }

        $service = app(ElectionService::class);
        $service->determineOnlineWinners($election);

        // Neither candidate is arbitrarily declared winner
        $this->assertFalse($candA->fresh()->is_winner);
        $this->assertFalse($candB->fresh()->is_winner);

        $results = $service->getOnlineResults($election);
        $this->assertTrue($results['has_ties']);
        $this->assertNull($results['results'][0]['winner']);
    }

    public function test_existing_non_tied_election_results_remain_unchanged(): void
    {
        $admin = $this->admin();

        $election = Election::factory()->create([
            'type' => Election::TYPE_ONLINE,
            'status' => Election::STATUS_CLOSED,
            'term' => '2026-2028',
            'start_time' => now()->subDays(2),
            'end_time' => now()->subHour(),
        ]);

        // Position 1: Unambiguous winner
        $pos1 = ElectionPosition::factory()->create([
            'election_id' => $election->id,
            'name' => 'President',
            'display_order' => 1,
        ]);
        $candP1 = ElectionCandidate::factory()->create(['election_id' => $election->id, 'election_position_id' => $pos1->id]);
        $candP2 = ElectionCandidate::factory()->create(['election_id' => $election->id, 'election_position_id' => $pos1->id]);

        // Position 2: Tied
        $pos2 = ElectionPosition::factory()->create([
            'election_id' => $election->id,
            'name' => 'General Secretary',
            'display_order' => 2,
        ]);
        $candG1 = ElectionCandidate::factory()->create(['election_id' => $election->id, 'election_position_id' => $pos2->id]);
        $candG2 = ElectionCandidate::factory()->create(['election_id' => $election->id, 'election_position_id' => $pos2->id]);

        $v1 = $this->voter();
        $v2 = $this->voter();
        $v3 = $this->voter();

        // Pos 1 votes: candP1 (2 votes), candP2 (1 vote)
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $pos1->id, 'election_candidate_id' => $candP1->id, 'voter_id' => $v1->id]);
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $pos1->id, 'election_candidate_id' => $candP1->id, 'voter_id' => $v2->id]);
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $pos1->id, 'election_candidate_id' => $candP2->id, 'voter_id' => $v3->id]);

        // Pos 2 votes: candG1 (1 vote), candG2 (1 vote)
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $pos2->id, 'election_candidate_id' => $candG1->id, 'voter_id' => $v1->id]);
        ElectionVote::factory()->create(['election_id' => $election->id, 'election_position_id' => $pos2->id, 'election_candidate_id' => $candG2->id, 'voter_id' => $v2->id]);

        $service = app(ElectionService::class);
        $service->determineOnlineWinners($election);

        // Pos 1 has clear winner
        $this->assertTrue($candP1->fresh()->is_winner);
        $this->assertFalse($candP2->fresh()->is_winner);

        // Pos 2 is tied
        $this->assertFalse($candG1->fresh()->is_winner);
        $this->assertFalse($candG2->fresh()->is_winner);

        $results = $service->getOnlineResults($election);
        $this->assertTrue($results['has_ties']);

        // Resolve tie on Pos 2
        $this->actingAs($admin)->post(route('admin.elections.resolve-tie', $election), [
            'position_id' => $pos2->id,
            'candidate_id' => $candG1->id,
            'resolution_method' => 'committee_consensus',
            'resolution_reason' => 'Consensus decision',
            'confirm_resolution' => '1',
        ])->assertRedirect();

        // Verify Pos 1 winner remained unchanged!
        $this->assertTrue($candP1->fresh()->is_winner);
        $this->assertFalse($candP2->fresh()->is_winner);
        $this->assertFalse($pos1->fresh()->isTieResolved());

        // Verify Pos 2 winner is now candG1 with tie resolution
        $this->assertTrue($candG1->fresh()->is_winner);
        $this->assertFalse($candG2->fresh()->is_winner);
        $this->assertTrue($pos2->fresh()->isTieResolved());
    }
}
