<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Batch;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardBatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function createAlumniUser(Batch $batch, array $alumniAttributes = []): array
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $user->roles()->attach(Role::where('slug', 'alumni')->firstOrFail());

        $alumni = Alumni::factory()->create(array_merge([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'status' => Alumni::STATUS_ACTIVE,
            'full_name' => 'Test Alumnus',
            'student_id' => '2021001',
            'session' => '2020-2021',
        ], $alumniAttributes));

        return [$user, $alumni];
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('dashboard.batch'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_without_alumni_record_sees_unlinked_placeholder(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('dashboard.batch'));

        $response->assertOk();
        $response->assertSee('No alumni record linked');
    }

    public function test_user_without_assigned_batch_sees_no_batch_placeholder(): void
    {
        $user = User::factory()->create();
        $batch = Batch::factory()->create();
        $alumni = Alumni::factory()->create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'status' => Alumni::STATUS_ACTIVE,
        ]);

        // Mock the batch relation returning null to simulate a dangling or unassigned batch state
        $alumni->setRelation('batch', null);
        $user->setRelation('alumni', $alumni);

        $response = $this->actingAs($user)->get(route('dashboard.batch'));

        $response->assertOk();
        $response->assertSee('No batch assigned');
    }

    public function test_alumni_can_see_members_of_their_own_batch(): void
    {
        $batch = Batch::factory()->create([
            'batch_name' => 'Batch 05',
            'batch_code' => 'B05',
            'session' => '2019-2020',
        ]);

        [$currentUser, $currentAlumni] = $this->createAlumniUser($batch, [
            'full_name' => 'Alice Rahman',
            'student_id' => '1905001',
            'company' => 'Google',
            'designation' => 'Software Engineer',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
            'skills' => 'PHP, Laravel, Vue',
            'job_status' => Alumni::JOB_STATUS_EMPLOYED,
        ]);

        // Create a batchmate in the same batch
        Alumni::factory()->create([
            'batch_id' => $batch->id,
            'status' => Alumni::STATUS_ACTIVE,
            'full_name' => 'Bob Ahmed',
            'student_id' => '1905002',
            'company' => 'Microsoft',
            'designation' => 'Cloud Architect',
            'job_status' => Alumni::JOB_STATUS_EMPLOYED,
        ]);

        $response = $this->actingAs($currentUser)->get(route('dashboard.batch'));

        $response->assertOk();
        $response->assertSee('Batch 05');
        $response->assertSee('B05');
        $response->assertSee('Alice Rahman');
        $response->assertSee('1905001');
        $response->assertSee('Software Engineer');
        $response->assertSee('Google');
        $response->assertSee('Bob Ahmed');
        $response->assertSee('1905002');
        $response->assertSee('Cloud Architect');
        $response->assertSee('Microsoft');
    }

    public function test_alumni_cannot_see_or_browse_members_of_another_batch(): void
    {
        $myBatch = Batch::factory()->create(['batch_name' => 'My Batch 01', 'batch_code' => 'B01']);
        $otherBatch = Batch::factory()->create(['batch_name' => 'Other Batch 02', 'batch_code' => 'B02']);

        [$currentUser, $currentAlumni] = $this->createAlumniUser($myBatch, [
            'full_name' => 'My Batchmate',
            'student_id' => '1001',
        ]);

        // Create alumni in the other batch
        Alumni::factory()->create([
            'batch_id' => $otherBatch->id,
            'status' => Alumni::STATUS_ACTIVE,
            'full_name' => 'Secret Other Alumni',
            'student_id' => '2001',
            'company' => 'Confidential Corp',
        ]);

        // 1. Regular view
        $response = $this->actingAs($currentUser)->get(route('dashboard.batch'));
        $response->assertOk();
        $response->assertSee('My Batchmate');
        $response->assertDontSee('Secret Other Alumni');
        $response->assertDontSee('Confidential Corp');

        // 2. Tampering URL parameters with another batch ID must be strictly ignored
        $tamperedResponse = $this->actingAs($currentUser)->get(route('dashboard.batch', ['batch_id' => $otherBatch->id]));
        $tamperedResponse->assertOk();
        $tamperedResponse->assertSee('My Batchmate');
        $tamperedResponse->assertDontSee('Secret Other Alumni');
        $tamperedResponse->assertDontSee('Other Batch 02');
    }

    public function test_search_filters_batchmates_correctly(): void
    {
        $batch = Batch::factory()->create(['batch_name' => 'Batch 07', 'batch_code' => 'B07']);

        [$currentUser] = $this->createAlumniUser($batch, [
            'full_name' => 'Tariqul Islam',
            'student_id' => '1701001',
            'company' => 'Brain Station 23',
            'designation' => 'Lead Engineer',
        ]);

        Alumni::factory()->create([
            'batch_id' => $batch->id,
            'status' => Alumni::STATUS_ACTIVE,
            'full_name' => 'Mahmudul Hasan',
            'student_id' => '1701002',
            'company' => 'Optimizely',
            'designation' => 'Senior Developer',
        ]);

        // Search by name
        $resName = $this->actingAs($currentUser)->get(route('dashboard.batch', ['search' => 'Mahmudul']));
        $resName->assertOk();
        $resName->assertSee('Mahmudul Hasan');
        $resName->assertDontSee('Tariqul Islam');

        // Search by company
        $resCompany = $this->actingAs($currentUser)->get(route('dashboard.batch', ['search' => 'Brain Station']));
        $resCompany->assertOk();
        $resCompany->assertSee('Tariqul Islam');
        $resCompany->assertDontSee('Mahmudul Hasan');

        // Search by designation
        $resDesig = $this->actingAs($currentUser)->get(route('dashboard.batch', ['search' => 'Senior Developer']));
        $resDesig->assertOk();
        $resDesig->assertSee('Mahmudul Hasan');
        $resDesig->assertDontSee('Tariqul Islam');

        // Search with non-matching term
        $resEmpty = $this->actingAs($currentUser)->get(route('dashboard.batch', ['search' => 'NonExistentPerson']));
        $resEmpty->assertOk();
        $resEmpty->assertSee('No batchmates match');
    }

    public function test_sensitive_account_information_is_not_exposed(): void
    {
        $batch = Batch::factory()->create(['batch_name' => 'Batch 08', 'batch_code' => 'B08']);

        $userWithSensitiveData = User::factory()->create([
            'email' => 'very_secret_email@bup.edu.bd',
            'password' => bcrypt('SuperSecretPassword123!'),
        ]);

        $alumni = Alumni::factory()->create([
            'user_id' => $userWithSensitiveData->id,
            'batch_id' => $batch->id,
            'status' => Alumni::STATUS_ACTIVE,
            'full_name' => 'Sensitive Data User',
            'student_id' => '1801001',
            'phone' => '+8801700000000',
        ]);

        [$currentUser] = $this->createAlumniUser($batch, ['full_name' => 'Viewing Member']);

        $response = $this->actingAs($currentUser)->get(route('dashboard.batch'));
        $response->assertOk();
        $response->assertSee('Sensitive Data User');

        // Verify passwords and hashes are never in the rendered content
        $response->assertDontSee('SuperSecretPassword123!');
        $response->assertDontSee($userWithSensitiveData->password);
    }
}
