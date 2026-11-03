<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Batch;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAlumniNetworkManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function makeAdmin(): User
    {
        $user = User::factory()->create();
        $adminRole = Role::where('slug', 'admin')->firstOrFail();
        $user->roles()->attach($adminRole);

        return $user;
    }

    protected function makeSuperAdmin(): User
    {
        $user = User::factory()->create();
        $superAdminRole = Role::where('slug', 'super-admin')->firstOrFail();
        $user->roles()->attach($superAdminRole);

        return $user;
    }

    protected function makeBatchRep(): User
    {
        $user = User::factory()->create();
        $batchRepRole = Role::where('slug', 'batch-representative')->firstOrFail();
        $user->roles()->attach($batchRepRole);

        return $user;
    }

    public function test_admin_and_super_admin_can_access_network_management_dashboard(): void
    {
        $admin = $this->makeAdmin();
        $superAdmin = $this->makeSuperAdmin();

        $this->actingAs($admin)
            ->get(route('admin.alumni-network.index'))
            ->assertOk()
            ->assertSee('Alumni Network Management')
            ->assertSee('+ Add Alumni Information');

        $this->actingAs($superAdmin)
            ->get(route('admin.alumni-network.index'))
            ->assertOk()
            ->assertSee('Alumni Network Management');
    }

    public function test_unauthorized_users_and_batch_reps_cannot_access_network_management(): void
    {
        $batchRep = $this->makeBatchRep();
        $plainUser = User::factory()->create();

        $this->actingAs($batchRep)
            ->get(route('admin.alumni-network.index'))
            ->assertForbidden();

        $this->actingAs($plainUser)
            ->get(route('admin.alumni-network.index'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.alumni-network.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_filter_and_search_alumni_list(): void
    {
        $admin = $this->makeAdmin();
        $batch = Batch::factory()->create(['batch_name' => 'Batch 01', 'batch_code' => 'BICE-01']);

        $alumnus1 = Alumni::factory()->create([
            'student_id' => '1754101001',
            'full_name' => 'Alice Rahman',
            'batch_id' => $batch->id,
            'country' => 'Japan',
            'city' => 'Tokyo',
            'company' => 'Sony',
        ]);

        $alumnus2 = Alumni::factory()->create([
            'student_id' => '1754101002',
            'full_name' => 'Bob Hasan',
            'batch_id' => $batch->id,
            'country' => 'Bangladesh',
            'city' => 'Dhaka',
            'company' => 'Grameenphone',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.alumni-network.index', ['search' => 'Alice']));

        $response->assertOk();
        $response->assertSee('Alice Rahman');
        $response->assertDontSee('Bob Hasan');

        $responseCountry = $this->actingAs($admin)
            ->get(route('admin.alumni-network.index', ['country' => 'Japan']));

        $responseCountry->assertOk();
        $responseCountry->assertSee('Alice Rahman');
        $responseCountry->assertDontSee('Bob Hasan');
    }

    public function test_admin_can_create_new_alumni_information(): void
    {
        $admin = $this->makeAdmin();
        $batch = Batch::factory()->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.alumni-network.store'), [
                'student_id' => '1754101099',
                'full_name' => 'Charlie New',
                'batch_id' => $batch->id,
                'graduation_year' => 2023,
                'country' => 'Germany',
                'city' => 'Munich',
                'company' => 'BMW Tech',
                'designation' => 'Cloud Architect',
                'job_status' => Alumni::JOB_STATUS_EMPLOYED,
            ]);

        $response->assertRedirect(route('admin.alumni-network.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('alumni', [
            'student_id' => '1754101099',
            'full_name' => 'Charlie New',
            'country' => 'Germany',
            'city' => 'Munich',
            'status' => Alumni::STATUS_UNCLAIMED,
        ]);
    }

    public function test_duplicate_student_id_is_rejected_on_create(): void
    {
        $admin = $this->makeAdmin();
        $batch = Batch::factory()->create();

        Alumni::factory()->create([
            'student_id' => '1754101001',
            'full_name' => 'Existing Alumnus',
            'batch_id' => $batch->id,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.alumni-network.create'))
            ->post(route('admin.alumni-network.store'), [
                'student_id' => '1754101001',
                'full_name' => 'Duplicate Attempt',
                'batch_id' => $batch->id,
            ]);

        $response->assertRedirect(route('admin.alumni-network.create'));
        $response->assertSessionHasErrors(['student_id']);
    }

    public function test_admin_can_update_existing_alumnus_network_fields(): void
    {
        $admin = $this->makeAdmin();
        $batch = Batch::factory()->create();

        $alumnus = Alumni::factory()->create([
            'student_id' => '1754101050',
            'full_name' => 'Dana Update',
            'batch_id' => $batch->id,
            'country' => 'Bangladesh',
            'city' => 'Dhaka',
            'job_status' => Alumni::JOB_STATUS_EMPLOYED,
            'company' => 'Local Startup',
            'designation' => 'Developer',
        ]);

        $response = $this->actingAs($admin)
            ->put(route('admin.alumni-network.update', $alumnus), [
                'full_name' => 'Dana Update',
                'batch_id' => $batch->id,
                'country' => 'Canada',
                'city' => 'Vancouver',
                'job_status' => Alumni::JOB_STATUS_ENTREPRENEUR,
                'company' => 'North Cloud Solutions',
                'designation' => 'Founder & CEO',
                'skills' => 'Go, Kubernetes, AWS',
                'mentorship' => 1,
            ]);

        $response->assertRedirect(route('admin.alumni-network.index'));

        $alumnus->refresh();
        $this->assertEquals('Canada', $alumnus->country);
        $this->assertEquals('Vancouver', $alumnus->city);
        $this->assertEquals(Alumni::JOB_STATUS_ENTREPRENEUR, $alumnus->job_status);
        $this->assertEquals('North Cloud Solutions', $alumnus->company);
        $this->assertEquals('Founder & CEO', $alumnus->designation);
        $this->assertTrue($alumnus->mentorship);
        $this->assertTrue($alumnus->isAbroad());
        $this->assertTrue($alumnus->isEntrepreneur());
    }

    public function test_invalid_updates_are_rejected(): void
    {
        $admin = $this->makeAdmin();
        $batch = Batch::factory()->create();

        $alumnus = Alumni::factory()->create([
            'student_id' => '1754101051',
            'full_name' => 'Valid Alumnus',
            'batch_id' => $batch->id,
        ]);

        $response = $this->actingAs($admin)
            ->put(route('admin.alumni-network.update', $alumnus), [
                'full_name' => '', // required
                'batch_id' => 99999, // invalid batch
                'graduation_year' => 1800, // out of range
                'job_status' => 'InvalidJobStatus', // invalid
            ]);

        $response->assertSessionHasErrors(['full_name', 'batch_id', 'graduation_year', 'job_status']);
    }

    public function test_updating_profile_immediately_reflects_in_alumni_network_categories(): void
    {
        $admin = $this->makeAdmin();
        $batch = Batch::factory()->create();

        $alumnus = Alumni::factory()->active()->create([
            'student_id' => '1754101060',
            'full_name' => 'Dynamic Alumnus',
            'batch_id' => $batch->id,
            'country' => 'Bangladesh',
            'city' => 'Dhaka',
            'company' => 'Local Co',
            'designation' => 'Software Engineer',
            'job_status' => Alumni::JOB_STATUS_EMPLOYED,
        ]);

        // Initially in Professionals in Bangladesh
        $this->get('/alumni-network')->assertSee('Dynamic Alumnus');
        $this->assertTrue($alumnus->isProfessionalInBangladesh());
        $this->assertFalse($alumnus->isAbroad());

        // Admin updates location to USA and role to PhD Fellow at MIT
        $this->actingAs($admin)
            ->put(route('admin.alumni-network.update', $alumnus), [
                'full_name' => 'Dynamic Alumnus',
                'batch_id' => $batch->id,
                'country' => 'United States',
                'city' => 'Cambridge',
                'current_institution' => 'MIT',
                'higher_studies' => 'PhD in Computer Science',
                'designation' => 'Research Fellow',
                'job_status' => Alumni::JOB_STATUS_HIGHER_STUDIES,
            ]);

        $alumnus->refresh();

        $this->assertTrue($alumnus->isAbroad());
        $this->assertTrue($alumnus->isHigherStudiesAndResearch());
        $this->assertFalse($alumnus->isProfessionalInBangladesh());

        // Public page reflects new categories immediately
        $response = $this->get('/alumni-network');
        $response->assertOk();
        $response->assertSee('Cambridge, United States');
        $response->assertSee('PhD in Computer Science');
        $response->assertSee('MIT');
    }

    public function test_linked_user_accounts_and_verification_status_remain_intact_after_admin_update(): void
    {
        $admin = $this->makeAdmin();
        $batch = Batch::factory()->create();
        $user = User::factory()->create(['email' => 'user-alumni@example.com']);

        $alumnus = Alumni::factory()->create([
            'student_id' => '1754101070',
            'full_name' => 'Linked Alumnus',
            'batch_id' => $batch->id,
            'user_id' => $user->id,
            'status' => Alumni::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.alumni-network.update', $alumnus), [
                'full_name' => 'Linked Alumnus Renamed',
                'batch_id' => $batch->id,
                'country' => 'Australia',
                'city' => 'Sydney',
            ]);

        $alumnus->refresh();
        $this->assertEquals($user->id, $alumnus->user_id);
        $this->assertEquals(Alumni::STATUS_ACTIVE, $alumnus->status);
        $this->assertEquals('1754101070', $alumnus->student_id);
    }

    public function test_alumnus_self_service_update_immediately_reflects_in_network_category(): void
    {
        $batch = Batch::factory()->create();
        $user = User::factory()->create(['email' => 'selfservice@example.com']);

        $alumnus = Alumni::factory()->active()->create([
            'student_id' => '1754101080',
            'full_name' => 'Self Service Alumnus',
            'batch_id' => $batch->id,
            'user_id' => $user->id,
            'country' => 'Bangladesh',
            'company' => 'Old Co',
            'job_status' => Alumni::JOB_STATUS_EMPLOYED,
        ]);

        $this->actingAs($user)
            ->put(route('dashboard.profile.update'), [
                'email' => 'selfservice@example.com',
                'country' => 'Ireland',
                'city' => 'Dublin',
                'company' => 'Stripe',
                'designation' => 'Staff Engineer',
                'job_status' => Alumni::JOB_STATUS_EMPLOYED,
            ]);

        $alumnus->refresh();
        $this->assertEquals('Ireland', $alumnus->country);
        $this->assertEquals('Dublin', $alumnus->city);
        $this->assertTrue($alumnus->isAbroad());

        $response = $this->get('/alumni-network');
        $response->assertOk();
        $response->assertSee('Dublin, Ireland');
        $response->assertSee('Stripe');
    }
}
