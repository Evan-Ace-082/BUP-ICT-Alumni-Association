<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Batch;
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

class ElectionBrowserWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function userWithRole(string $slug, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['status' => User::STATUS_ACTIVE], $attributes));
        $user->roles()->attach(Role::where('slug', $slug)->firstOrFail());

        return $user;
    }

    public function test_admin_can_manage_candidates_multiple_per_position_and_start_election(): void
    {
        // 1. Create Admin User
        $admin = $this->userWithRole('admin', ['email' => 'admin_test_flow@bup.edu.bd']);

        // 2. Ensure Batch & Eligible Alumni exist
        $batch = Batch::factory()->create([
            'batch_name' => 'Batch 20 (2020)',
            'batch_code' => 'B20',
            'graduation_year' => 2024,
        ]);

        $evanUser = $this->userWithRole('alumni', ['name' => 'Nabil Hasan Evan', 'email' => 'evan_flow_test@bup.edu.bd']);
        $evanAlumni = Alumni::create([
            'user_id' => $evanUser->id,
            'student_id' => '160101',
            'full_name' => 'Nabil Hasan Evan',
            'batch_id' => $batch->id,
            'status' => Alumni::STATUS_ACTIVE,
            'graduation_year' => 2024,
            'degree' => 'B.Sc. in ICT',
        ]);

        $tasinUser = $this->userWithRole('alumni', ['name' => 'Tasin Ahmed', 'email' => 'tasin_flow_test@bup.edu.bd']);
        $tasinAlumni = Alumni::create([
            'user_id' => $tasinUser->id,
            'student_id' => '160102',
            'full_name' => 'Tasin Ahmed',
            'batch_id' => $batch->id,
            'status' => Alumni::STATUS_ACTIVE,
            'graduation_year' => 2024,
            'degree' => 'B.Sc. in ICT',
        ]);

        $gsUser = $this->userWithRole('alumni', ['name' => 'Rafiqul Islam', 'email' => 'gs_flow_test@bup.edu.bd']);
        $gsAlumni = Alumni::create([
            'user_id' => $gsUser->id,
            'student_id' => '160103',
            'full_name' => 'Rafiqul Islam',
            'batch_id' => $batch->id,
            'status' => Alumni::STATUS_ACTIVE,
            'graduation_year' => 2024,
            'degree' => 'B.Sc. in ICT',
        ]);

        // 3. Create Draft Election with start_time in future (upcoming)
        $election = Election::create([
            'title' => 'Executive Committee Election 2026-2028 (Workflow Test)',
            'term' => '2026-2028',
            'type' => Election::TYPE_ONLINE,
            'status' => Election::STATUS_DRAFT,
            'start_time' => now()->addHour(),
            'end_time' => now()->addDays(2),
            'created_by' => $admin->id,
        ]);

        $posPres = ElectionPosition::create([
            'election_id' => $election->id,
            'name' => 'President',
            'display_order' => 1,
        ]);

        $posGs = ElectionPosition::create([
            'election_id' => $election->id,
            'name' => 'General Secretary',
            'display_order' => 2,
        ]);

        // 4. Verify Election Details Page renders with Add Candidate forms & candidate selector options
        $response = $this->actingAs($admin)->get(route('admin.elections.show', $election));
        $response->assertStatus(200);
        $response->assertSee('President');
        $response->assertSee('General Secretary');
        $response->assertSee('Nabil Hasan Evan — Student ID: 160101');
        $response->assertSee('Tasin Ahmed — Student ID: 160102');

        // 5. Try to start election while positions have no candidates -> MUST fail validation with clear message
        $startFail = $this->actingAs($admin)->patch(route('admin.elections.start', $election));
        $startFail->assertSessionHas('error');
        $this->assertEquals(Election::STATUS_DRAFT, $election->fresh()->status);

        // 6. Add Candidate 1 for President (Evan)
        $cand1 = $this->actingAs($admin)->post(route('admin.elections.candidates.store', $election), [
            'election_position_id' => $posPres->id,
            'alumni_id' => $evanAlumni->id,
            'statement' => 'Vision for excellence in ICT Alumni.',
        ]);
        $cand1->assertSessionHas('success');

        // 7. Add Candidate 2 for President (Tasin) - Multiple candidates for the SAME position
        $cand2 = $this->actingAs($admin)->post(route('admin.elections.candidates.store', $election), [
            'election_position_id' => $posPres->id,
            'alumni_id' => $tasinAlumni->id,
            'statement' => 'Empowering every graduate with global opportunities.',
        ]);
        $cand2->assertSessionHas('success');

        // Confirm 2 candidates exist under President
        $this->assertEquals(2, ElectionCandidate::where('election_position_id', $posPres->id)->count());

        // Prevent duplicate nomination of Evan in the SAME election
        $duplicate = $this->actingAs($admin)->post(route('admin.elections.candidates.store', $election), [
            'election_position_id' => $posGs->id,
            'alumni_id' => $evanAlumni->id,
        ]);
        $duplicate->assertSessionHasErrors('alumni_id');

        // 8. Add Candidate for General Secretary (Rafiqul)
        $cand3 = $this->actingAs($admin)->post(route('admin.elections.candidates.store', $election), [
            'election_position_id' => $posGs->id,
            'alumni_id' => $gsAlumni->id,
            'statement' => 'Transparent administration and active networking.',
        ]);
        $cand3->assertSessionHas('success');

        // 9. Now start the election -> MUST succeed because every position has >= 1 candidate
        $startSuccess = $this->actingAs($admin)->patch(route('admin.elections.start', $election));
        $startSuccess->assertSessionHas('success');
        $this->assertEquals(Election::STATUS_ONGOING, $election->fresh()->status);

        // 10. Check Alumni Dashboard elections index:
        // Election is ONGOING and upcoming preview is visible
        $dashboardIndex = $this->actingAs($evanUser)->get(route('dashboard.elections.index'));
        $dashboardIndex->assertStatus(200);
        $dashboardIndex->assertSee('Executive Committee Election 2026-2028 (Workflow Test)');

        // 11. Adjust start_time to past so voting window is actively open
        $election->update([
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);

        // 12. Check Alumni Voting page renders the candidates under their positions
        $ballot = $this->actingAs($evanUser)->get(route('dashboard.elections.show', $election));
        $ballot->assertStatus(200);
        $ballot->assertSee('Nabil Hasan Evan');
        $ballot->assertSee('Tasin Ahmed');
        $ballot->assertSee('Rafiqul Islam');
        $ballot->assertSee('Cast Your Vote');

        // 13. Evan votes for President (votes for Tasin)
        $tasinCandidate = ElectionCandidate::where('election_position_id', $posPres->id)
            ->where('alumni_id', $tasinAlumni->id)
            ->first();

        $vote1 = $this->actingAs($evanUser)->post(route('dashboard.elections.vote', $election), [
            'position_id' => $posPres->id,
            'candidate_id' => $tasinCandidate->id,
        ]);
        $vote1->assertSessionHas('success');

        // 14. Cannot vote twice for President in the same election
        $voteDuplicate = $this->actingAs($evanUser)->post(route('dashboard.elections.vote', $election), [
            'position_id' => $posPres->id,
            'candidate_id' => $tasinCandidate->id,
        ]);
        $voteDuplicate->assertSessionHas('error');

        // 15. Evan CAN vote separately for General Secretary
        $gsCandidate = ElectionCandidate::where('election_position_id', $posGs->id)
            ->where('alumni_id', $gsAlumni->id)
            ->first();

        $vote2 = $this->actingAs($evanUser)->post(route('dashboard.elections.vote', $election), [
            'position_id' => $posGs->id,
            'candidate_id' => $gsCandidate->id,
        ]);
        $vote2->assertSessionHas('success');

        // 16. Verify votes are securely recorded
        $this->assertEquals(2, ElectionVote::where('election_id', $election->id)->where('voter_id', $evanUser->id)->count());
    }

    public function test_physical_election_records_winners_and_finalizes_committee(): void
    {
        $admin = $this->userWithRole('admin', ['email' => 'admin_test_flow@bup.edu.bd']);

        $batch = Batch::factory()->create([
            'batch_name' => 'Batch 20 (2020)',
            'batch_code' => 'B20-PHYS',
            'graduation_year' => 2024,
        ]);

        $presUser = $this->userWithRole('alumni', ['name' => 'Physical President Winner', 'email' => 'pres_phys@bup.edu.bd']);
        $presAlumni = Alumni::create([
            'user_id' => $presUser->id,
            'student_id' => 'PHYS001',
            'full_name' => 'Physical President Winner',
            'batch_id' => $batch->id,
            'status' => Alumni::STATUS_ACTIVE,
            'graduation_year' => 2024,
            'degree' => 'B.Sc. in ICT',
        ]);

        // Physical Election
        $election = Election::create([
            'title' => 'Physical AGM Election 2026-2028',
            'term' => '2026-2028',
            'type' => Election::TYPE_PHYSICAL,
            'status' => Election::STATUS_CLOSED,
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDay(),
            'created_by' => $admin->id,
        ]);

        $pos = ElectionPosition::create([
            'election_id' => $election->id,
            'name' => 'President',
            'display_order' => 1,
        ]);

        $cand = ElectionCandidate::create([
            'election_id' => $election->id,
            'election_position_id' => $pos->id,
            'alumni_id' => $presAlumni->id,
            'is_winner' => false,
        ]);

        // 1. Alumni visiting a physical election sees NO online ballot (aborts 404)
        $alumniView = $this->actingAs($presUser)->get(route('dashboard.elections.show', $election));
        $alumniView->assertStatus(404);

        // 2. Admin records physical winner for President
        $selectWinner = $this->actingAs($admin)->post(route('admin.elections.select-physical-winner', $election), [
            'position_id' => $pos->id,
            'candidate_id' => $cand->id,
        ]);
        $selectWinner->assertSessionHas('success');
        $this->assertTrue($cand->fresh()->is_winner);

        // 3. Admin finalizes physical election
        $finalize = $this->actingAs($admin)->patch(route('admin.elections.finalize', $election));
        $finalize->assertSessionHas('success');

        // 3. Confirm election is FINALIZED and winner marked
        $this->assertEquals(Election::STATUS_FINALIZED, $election->fresh()->status);
        $this->assertTrue($cand->fresh()->is_winner);

        // 4. Confirm Committee and CommitteeMember created
        $committee = Committee::where('election_id', $election->id)->first();
        $this->assertNotNull($committee);
        $this->assertEquals('2026-2028', $committee->term);
        $this->assertEquals(1, $committee->members()->count());
        $this->assertEquals($presAlumni->id, $committee->members()->first()->alumni_id);
    }
}
