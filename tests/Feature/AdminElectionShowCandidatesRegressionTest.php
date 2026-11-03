<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Batch;
use App\Models\Election;
use App\Models\ElectionCandidate;
use App\Models\ElectionPosition;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminElectionShowCandidatesRegressionTest extends TestCase
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

    public function test_admin_elections_show_renders_positions_and_candidate_nomination_complete_workflow(): void
    {
        $admin = $this->userWithRole('admin', ['email' => 'admin_show_regression@bup.edu.bd']);

        $batch = Batch::factory()->create([
            'batch_name' => 'Batch 20 (2020)',
            'batch_code' => 'B20',
            'graduation_year' => 2024,
        ]);

        $alumni1User = $this->userWithRole('alumni', ['name' => 'Nabil Hasan Evan', 'email' => 'evan_reg@bup.edu.bd']);
        $alumni1 = Alumni::create([
            'user_id' => $alumni1User->id,
            'student_id' => '160101',
            'full_name' => 'Nabil Hasan Evan',
            'batch_id' => $batch->id,
            'status' => Alumni::STATUS_ACTIVE,
            'graduation_year' => 2024,
            'degree' => 'B.Sc. in ICT',
        ]);

        $alumni2User = $this->userWithRole('alumni', ['name' => 'Tasin Ahmed', 'email' => 'tasin_reg@bup.edu.bd']);
        $alumni2 = Alumni::create([
            'user_id' => $alumni2User->id,
            'student_id' => '160102',
            'full_name' => 'Tasin Ahmed',
            'batch_id' => $batch->id,
            'status' => Alumni::STATUS_ACTIVE,
            'graduation_year' => 2024,
            'degree' => 'B.Sc. in ICT',
        ]);

        $alumni3User = $this->userWithRole('alumni', ['name' => 'Rafiqul Islam', 'email' => 'rafiq_reg@bup.edu.bd']);
        $alumni3 = Alumni::create([
            'user_id' => $alumni3User->id,
            'student_id' => '160103',
            'full_name' => 'Rafiqul Islam',
            'batch_id' => $batch->id,
            'status' => Alumni::STATUS_ACTIVE,
            'graduation_year' => 2024,
            'degree' => 'B.Sc. in ICT',
        ]);

        $election = Election::create([
            'title' => 'Executive Committee Election 2026-2028',
            'term' => '2026-2028',
            'type' => Election::TYPE_ONLINE,
            'status' => Election::STATUS_DRAFT,
            'start_time' => now()->addHour(),
            'end_time' => now()->addDays(2),
            'created_by' => $admin->id,
        ]);

        $allConfiguredPositions = [
            'President',
            'Vice President',
            'General Secretary',
            'Joint Secretary',
            'Treasurer',
            'Organizing Secretary',
            'Executive Member',
            'Logistics',
            'Assistant General Secretary',
            'Assistant Joint Secretary',
        ];

        $createdPositions = [];
        foreach ($allConfiguredPositions as $index => $posName) {
            $createdPositions[$posName] = ElectionPosition::create([
                'election_id' => $election->id,
                'name' => $posName,
                'display_order' => $index + 1,
            ]);
        }

        // 1. Requirement 1: The election details response renders the Positions & Candidate Nomination heading
        $response = $this->actingAs($admin)->get(route('admin.elections.show', $election));
        $response->assertStatus(200);
        $response->assertSee('Positions & Candidate Nomination');

        // 2. Requirement 2: All configured positions and their Add Candidate buttons appear in the rendered HTML
        foreach ($allConfiguredPositions as $posName) {
            $response->assertSee($posName);
            $response->assertSee("+ Add Candidate to {$posName}");
        }
        $response->assertSee('+ Add Candidate');

        // 3. Requirement 3: The candidate dropdown contains eligible database-backed alumni with Student ID and batch
        $response->assertSee('Nabil Hasan Evan — Student ID: 160101 (Batch: Batch 20 (2020))');
        $response->assertSee('Tasin Ahmed — Student ID: 160102 (Batch: Batch 20 (2020))');
        $response->assertSee('Rafiqul Islam — Student ID: 160103 (Batch: Batch 20 (2020))');
        $response->assertSee(route('admin.elections.candidates.store', $election));

        // 4. Requirement 6: Starting an election with a vacant position is BLOCKED
        $startBlocked = $this->actingAs($admin)->patch(route('admin.elections.start', $election));
        $startBlocked->assertRedirect();
        $startBlocked->assertSessionHasErrors('candidates');
        $this->assertEquals(Election::STATUS_DRAFT, $election->fresh()->status);

        // 5. Requirement 4 & 5: Candidate creation persists to correct position & multiple candidates can run for one position
        // Evan runs for President
        $addCand1 = $this->actingAs($admin)->post(route('admin.elections.candidates.store', $election), [
            'election_position_id' => $createdPositions['President']->id,
            'alumni_id' => $alumni1->id,
            'statement' => 'My presidential manifesto',
        ]);
        $addCand1->assertRedirect();
        $addCand1->assertSessionHas('success');

        $this->assertDatabaseHas('election_candidates', [
            'election_id' => $election->id,
            'election_position_id' => $createdPositions['President']->id,
            'alumni_id' => $alumni1->id,
            'statement' => 'My presidential manifesto',
        ]);

        // Tasin also runs for President (multiple candidates in same position)
        $addCand2 = $this->actingAs($admin)->post(route('admin.elections.candidates.store', $election), [
            'election_position_id' => $createdPositions['President']->id,
            'alumni_id' => $alumni2->id,
            'statement' => 'Competing vision for presidency',
        ]);
        $addCand2->assertRedirect();
        $addCand2->assertSessionHas('success');

        $this->assertDatabaseHas('election_candidates', [
            'election_id' => $election->id,
            'election_position_id' => $createdPositions['President']->id,
            'alumni_id' => $alumni2->id,
        ]);

        // Prevent duplicate nomination of same alumnus in same election
        $duplicateAttempt = $this->actingAs($admin)->post(route('admin.elections.candidates.store', $election), [
            'election_position_id' => $createdPositions['Vice President']->id,
            'alumni_id' => $alumni1->id,
        ]);
        $duplicateAttempt->assertSessionHasErrors('alumni_id');

        // Check show view after candidates added to President
        $responseAfter = $this->actingAs($admin)->get(route('admin.elections.show', $election));
        $responseAfter->assertStatus(200);
        $responseAfter->assertSee('Nabil Hasan Evan');
        $responseAfter->assertSee('Student ID:</strong> 160101', false);
        $responseAfter->assertSee('Tasin Ahmed');
        $responseAfter->assertSee('Student ID:</strong> 160102', false);
        $responseAfter->assertSee('+ Add Candidate to President');
        $responseAfter->assertSee('Edit Candidate');

        // Edit Tasin's candidate record (e.g. statement)
        $candTasin = ElectionCandidate::where('election_position_id', $createdPositions['President']->id)
            ->where('alumni_id', $alumni2->id)
            ->firstOrFail();

        $updateCand = $this->actingAs($admin)->put(route('admin.elections.candidates.update', [$election, $candTasin]), [
            'election_position_id' => $createdPositions['President']->id,
            'statement' => 'Updated manifesto for President',
        ]);
        $updateCand->assertRedirect();
        $updateCand->assertSessionHas('success');
        $this->assertEquals('Updated manifesto for President', $candTasin->fresh()->statement);

        // Populate remaining positions so every position has at least one candidate
        $remainingPositions = array_slice($allConfiguredPositions, 1);
        foreach ($remainingPositions as $remIdx => $posName) {
            $otherAlumni = Alumni::create([
                'student_id' => '170' . sprintf('%03d', $remIdx + 1),
                'full_name' => "Candidate for {$posName}",
                'batch_id' => $batch->id,
                'status' => Alumni::STATUS_ACTIVE,
                'graduation_year' => 2024,
            ]);

            $this->actingAs($admin)->post(route('admin.elections.candidates.store', $election), [
                'election_position_id' => $createdPositions[$posName]->id,
                'alumni_id' => $otherAlumni->id,
            ]);
        }

        // 6. Requirement 7: Starting election after ALL positions have candidates SUCCEEDS
        $startSuccess = $this->actingAs($admin)->patch(route('admin.elections.start', $election));
        $startSuccess->assertRedirect();
        $startSuccess->assertSessionHas('success');
        $this->assertEquals(Election::STATUS_ONGOING, $election->fresh()->status);
    }
}
