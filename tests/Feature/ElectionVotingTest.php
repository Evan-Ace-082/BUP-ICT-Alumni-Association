<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Election;
use App\Models\ElectionCandidate;
use App\Models\ElectionPosition;
use App\Models\ElectionVote;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElectionVotingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function eligibleAlumnus(): User
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $user->roles()->attach(Role::where('slug', 'alumni')->firstOrFail());
        Alumni::factory()->active()->create([
            'user_id' => $user->id,
            'status' => Alumni::STATUS_ACTIVE,
        ]);

        return $user;
    }

    protected function ongoingElection(): Election
    {
        $election = Election::factory()->ongoing()->create([
            'type' => Election::TYPE_ONLINE,
        ]);

        $position = ElectionPosition::factory()->create([
            'election_id' => $election->id,
            'name' => 'President',
        ]);

        ElectionCandidate::factory()->count(2)->create([
            'election_id' => $election->id,
            'election_position_id' => $position->id,
        ]);

        return $election;
    }

    public function test_guest_cannot_vote_and_is_redirected_to_login(): void
    {
        $election = $this->ongoingElection();
        $position = $election->positions->first();
        $candidate = $position->candidates->first();

        $this->post("/dashboard/elections/{$election->id}/vote", [
            'position_id' => $position->id,
            'candidate_id' => $candidate->id,
        ])->assertRedirect(route('login'));
    }

    public function test_eligible_alumnus_can_successfully_cast_a_vote(): void
    {
        $voter = $this->eligibleAlumnus();
        $election = $this->ongoingElection();
        $position = $election->positions->first();
        $candidate = $position->candidates->first();

        $response = $this->actingAs($voter)->post("/dashboard/elections/{$election->id}/vote", [
            'position_id' => $position->id,
            'candidate_id' => $candidate->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('election_votes', [
            'election_id' => $election->id,
            'election_position_id' => $position->id,
            'election_candidate_id' => $candidate->id,
            'voter_id' => $voter->id,
        ]);
    }

    public function test_suspended_user_cannot_vote(): void
    {
        $voter = $this->eligibleAlumnus();
        $voter->update(['status' => User::STATUS_SUSPENDED]);

        $election = $this->ongoingElection();
        $position = $election->positions->first();
        $candidate = $position->candidates->first();

        $this->actingAs($voter)->post("/dashboard/elections/{$election->id}/vote", [
            'position_id' => $position->id,
            'candidate_id' => $candidate->id,
        ])->assertSessionHasErrors('voter');

        $this->assertDatabaseMissing('election_votes', [
            'voter_id' => $voter->id,
        ]);
    }

    public function test_ineligible_alumnus_with_unapproved_status_cannot_vote(): void
    {
        $voter = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $voter->roles()->attach(Role::where('slug', 'alumni')->firstOrFail());
        Alumni::factory()->create([
            'user_id' => $voter->id,
            'status' => Alumni::STATUS_PENDING,
        ]);

        $election = $this->ongoingElection();
        $position = $election->positions->first();
        $candidate = $position->candidates->first();

        $this->actingAs($voter)->post("/dashboard/elections/{$election->id}/vote", [
            'position_id' => $position->id,
            'candidate_id' => $candidate->id,
        ])->assertSessionHasErrors('voter');
    }

    public function test_user_without_linked_alumni_profile_cannot_vote(): void
    {
        $voter = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        $election = $this->ongoingElection();
        $position = $election->positions->first();
        $candidate = $position->candidates->first();

        $this->actingAs($voter)->post("/dashboard/elections/{$election->id}/vote", [
            'position_id' => $position->id,
            'candidate_id' => $candidate->id,
        ])->assertSessionHasErrors('voter');
    }

    public function test_voting_is_blocked_outside_scheduled_window(): void
    {
        $voter = $this->eligibleAlumnus();
        $election = Election::factory()->create([
            'type' => Election::TYPE_ONLINE,
            'status' => Election::STATUS_ONGOING,
            'start_time' => now()->subDays(5),
            'end_time' => now()->subDay(), // Expired window
        ]);
        $position = ElectionPosition::factory()->create(['election_id' => $election->id]);
        $candidate = ElectionCandidate::factory()->create([
            'election_id' => $election->id,
            'election_position_id' => $position->id,
        ]);

        $this->actingAs($voter)->post("/dashboard/elections/{$election->id}/vote", [
            'position_id' => $position->id,
            'candidate_id' => $candidate->id,
        ])->assertSessionHasErrors('election');
    }

    public function test_voting_is_blocked_when_election_is_draft_or_closed(): void
    {
        $voter = $this->eligibleAlumnus();

        $draftElection = Election::factory()->create(['status' => Election::STATUS_DRAFT]);
        $draftPos = ElectionPosition::factory()->create(['election_id' => $draftElection->id]);
        $draftCand = ElectionCandidate::factory()->create([
            'election_id' => $draftElection->id,
            'election_position_id' => $draftPos->id,
        ]);

        $this->actingAs($voter)->post("/dashboard/elections/{$draftElection->id}/vote", [
            'position_id' => $draftPos->id,
            'candidate_id' => $draftCand->id,
        ])->assertSessionHasErrors('election');

        $closedElection = Election::factory()->closed()->create();
        $closedPos = ElectionPosition::factory()->create(['election_id' => $closedElection->id]);
        $closedCand = ElectionCandidate::factory()->create([
            'election_id' => $closedElection->id,
            'election_position_id' => $closedPos->id,
        ]);

        $this->actingAs($voter)->post("/dashboard/elections/{$closedElection->id}/vote", [
            'position_id' => $closedPos->id,
            'candidate_id' => $closedCand->id,
        ])->assertSessionHasErrors('election');
    }

    public function test_voting_is_blocked_for_physical_elections(): void
    {
        $voter = $this->eligibleAlumnus();
        $physicalElection = Election::factory()->physical()->ongoing()->create();
        $pos = ElectionPosition::factory()->create(['election_id' => $physicalElection->id]);
        $cand = ElectionCandidate::factory()->create([
            'election_id' => $physicalElection->id,
            'election_position_id' => $pos->id,
        ]);

        $this->actingAs($voter)->post("/dashboard/elections/{$physicalElection->id}/vote", [
            'position_id' => $pos->id,
            'candidate_id' => $cand->id,
        ])->assertSessionHasErrors('election');
    }

    public function test_duplicate_vote_for_the_same_position_is_blocked(): void
    {
        $voter = $this->eligibleAlumnus();
        $election = $this->ongoingElection();
        $position = $election->positions->first();
        $candidates = $position->candidates;

        // First vote succeeds
        $this->actingAs($voter)->post("/dashboard/elections/{$election->id}/vote", [
            'position_id' => $position->id,
            'candidate_id' => $candidates[0]->id,
        ])->assertRedirect();

        // Second vote for the same position (even for a different candidate) is blocked
        $this->actingAs($voter)->post("/dashboard/elections/{$election->id}/vote", [
            'position_id' => $position->id,
            'candidate_id' => $candidates[1]->id,
        ])->assertSessionHasErrors('vote');

        $this->assertEquals(1, ElectionVote::where('voter_id', $voter->id)->count());
    }

    public function test_voter_can_vote_for_multiple_different_positions_in_same_election(): void
    {
        $voter = $this->eligibleAlumnus();
        $election = $this->ongoingElection();
        $pos1 = $election->positions->first();

        $pos2 = ElectionPosition::factory()->create([
            'election_id' => $election->id,
            'name' => 'General Secretary',
        ]);
        $cand2 = ElectionCandidate::factory()->create([
            'election_id' => $election->id,
            'election_position_id' => $pos2->id,
        ]);

        // Vote for Position 1
        $this->actingAs($voter)->post("/dashboard/elections/{$election->id}/vote", [
            'position_id' => $pos1->id,
            'candidate_id' => $pos1->candidates->first()->id,
        ])->assertRedirect();

        // Vote for Position 2 succeeds
        $this->actingAs($voter)->post("/dashboard/elections/{$election->id}/vote", [
            'position_id' => $pos2->id,
            'candidate_id' => $cand2->id,
        ])->assertRedirect();

        $this->assertEquals(2, ElectionVote::where('voter_id', $voter->id)->count());
    }

    public function test_voting_for_candidate_from_wrong_position_is_rejected(): void
    {
        $voter = $this->eligibleAlumnus();
        $election = $this->ongoingElection();
        $pos1 = $election->positions->first();

        $pos2 = ElectionPosition::factory()->create([
            'election_id' => $election->id,
            'name' => 'General Secretary',
        ]);
        $cand2 = ElectionCandidate::factory()->create([
            'election_id' => $election->id,
            'election_position_id' => $pos2->id,
        ]);

        // Trying to cast vote for Position 1 with Candidate from Position 2
        $this->actingAs($voter)->post("/dashboard/elections/{$election->id}/vote", [
            'position_id' => $pos1->id,
            'candidate_id' => $cand2->id,
        ])->assertSessionHasErrors('candidate');
    }

    public function test_voter_identity_cannot_be_spoofed_from_payload(): void
    {
        $voter = $this->eligibleAlumnus();
        $victim = $this->eligibleAlumnus();
        $election = $this->ongoingElection();
        $position = $election->positions->first();
        $candidate = $position->candidates->first();

        // Submitting fake voter_id in request payload
        $this->actingAs($voter)->post("/dashboard/elections/{$election->id}/vote", [
            'position_id' => $position->id,
            'candidate_id' => $candidate->id,
            'voter_id' => $victim->id,
        ])->assertRedirect();

        // Vote must belong to authenticated $voter, not $victim
        $this->assertDatabaseHas('election_votes', [
            'voter_id' => $voter->id,
            'election_candidate_id' => $candidate->id,
        ]);
        $this->assertDatabaseMissing('election_votes', [
            'voter_id' => $victim->id,
        ]);
    }

    public function test_upcoming_election_is_displayed_on_dashboard_index(): void
    {
        $voter = $this->eligibleAlumnus();
        $upcomingElection = Election::factory()->create([
            'type' => Election::TYPE_ONLINE,
            'status' => Election::STATUS_ONGOING,
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(4),
            'title' => 'Upcoming 2027 Election',
        ]);

        $response = $this->actingAs($voter)->get('/dashboard/elections');
        $response->assertOk();
        $response->assertSee('Upcoming 2027 Election');
        $response->assertSee('Upcoming Online Elections');
    }

    public function test_empty_elections_shows_placeholder_message(): void
    {
        $voter = $this->eligibleAlumnus();

        $response = $this->actingAs($voter)->get('/dashboard/elections');
        $response->assertOk();
        $response->assertSee('No Elections Available');
    }

    public function test_unlinked_user_cannot_vote(): void
    {
        $userWithoutAlumni = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $userWithoutAlumni->roles()->attach(Role::where('slug', 'alumni')->firstOrFail());
        $election = $this->ongoingElection();
        $position = $election->positions->first();
        $candidate = $position->candidates->first();

        $this->actingAs($userWithoutAlumni)->post("/dashboard/elections/{$election->id}/vote", [
            'position_id' => $position->id,
            'candidate_id' => $candidate->id,
        ])->assertSessionHasErrors('voter');
    }

    public function test_inactive_alumni_profile_cannot_vote(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $user->roles()->attach(Role::where('slug', 'alumni')->firstOrFail());
        Alumni::factory()->create([
            'user_id' => $user->id,
            'status' => Alumni::STATUS_INACTIVE,
        ]);

        $election = $this->ongoingElection();
        $position = $election->positions->first();
        $candidate = $position->candidates->first();

        $this->actingAs($user)->post("/dashboard/elections/{$election->id}/vote", [
            'position_id' => $position->id,
            'candidate_id' => $candidate->id,
        ])->assertSessionHasErrors('voter');
    }
}
