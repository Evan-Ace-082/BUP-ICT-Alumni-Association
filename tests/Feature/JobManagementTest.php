<?php

namespace Tests\Feature;

use App\Models\JobListing;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobManagementTest extends TestCase
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

    public function test_alumni_can_create_a_job(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/dashboard/jobs', [
            'title' => 'Backend Engineer', 'company' => 'Acme', 'description' => 'Details.', 'employment_type' => 'Full Time',
        ])->assertRedirect(route('dashboard.jobs.index'));

        $this->assertDatabaseHas('job_listings', ['title' => 'Backend Engineer', 'posted_by' => $user->id]);
    }

    public function test_alumni_can_edit_their_own_job(): void
    {
        $user = User::factory()->create();
        $job = JobListing::factory()->create(['posted_by' => $user->id, 'title' => 'Original Title']);

        $this->actingAs($user)->put("/dashboard/jobs/{$job->id}", [
            'title' => 'Updated Title', 'company' => $job->company, 'description' => 'Updated.', 'employment_type' => 'Full Time',
        ]);

        $this->assertDatabaseHas('job_listings', ['id' => $job->id, 'title' => 'Updated Title']);
    }

    public function test_alumni_cannot_edit_another_users_job(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $job = JobListing::factory()->create(['posted_by' => $owner->id, 'title' => 'Original Title']);

        $this->actingAs($intruder)->put("/dashboard/jobs/{$job->id}", [
            'title' => 'Hijacked', 'company' => $job->company, 'description' => 'x', 'employment_type' => 'Full Time',
        ])->assertForbidden();

        $this->assertDatabaseHas('job_listings', ['id' => $job->id, 'title' => 'Original Title']);
    }

    public function test_published_jobs_are_publicly_visible(): void
    {
        JobListing::factory()->create(['title' => 'Visible Job']);

        $this->get('/jobs')->assertOk()->assertSee('Visible Job');
    }

    public function test_closed_jobs_are_not_publicly_visible(): void
    {
        $job = JobListing::factory()->closed()->create(['title' => 'Closed Job']);

        $this->get('/jobs')->assertOk()->assertDontSee('Closed Job');
        $this->get("/jobs/{$job->id}")->assertNotFound();
    }

    public function test_admin_can_moderate_remove_a_job(): void
    {
        $admin = $this->admin();
        $job = JobListing::factory()->create();

        $this->actingAs($admin)->delete("/admin/jobs/{$job->id}")->assertRedirect();

        $this->assertDatabaseMissing('job_listings', ['id' => $job->id]);
    }

    public function test_a_regular_alumni_cannot_moderate_jobs(): void
    {
        $user = User::factory()->create();
        $job = JobListing::factory()->create();

        $this->actingAs($user)->delete("/admin/jobs/{$job->id}")->assertForbidden();
    }

    public function test_admin_can_access_create_job_page(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get('/admin/jobs/create')
            ->assertOk()
            ->assertSee('Create a New Job Listing');
    }

    public function test_admin_can_create_and_publish_a_job(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post('/admin/jobs', [
            'title' => 'Lead Architect',
            'company' => 'Tech Corp',
            'description' => 'Architect needed.',
            'employment_type' => 'Full Time',
            'location' => 'Dhaka',
            'country' => 'Bangladesh',
            'salary' => '150,000 BDT',
            'application_url' => 'https://techcorp.com/apply',
            'application_email' => 'hr@techcorp.com',
            'deadline' => '2026-12-31',
            'status' => 'published',
        ]);

        $response->assertRedirect(route('admin.jobs.index'));

        $this->assertDatabaseHas('job_listings', [
            'title' => 'Lead Architect',
            'company' => 'Tech Corp',
            'posted_by' => $admin->id,
            'status' => 'published',
        ]);

        // Verify it is publicly visible on the public Job Board
        $this->get('/jobs')->assertOk()->assertSee('Lead Architect');
    }

    public function test_admin_can_save_job_as_draft_and_publish_later(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/jobs', [
            'title' => 'Draft Position',
            'company' => 'Startup Inc',
            'description' => 'Upcoming role.',
            'employment_type' => 'Remote',
            'status' => 'draft',
        ])->assertRedirect(route('admin.jobs.index'));

        $job = JobListing::where('title', 'Draft Position')->firstOrFail();
        $this->assertEquals('draft', $job->status);

        // Draft is NOT on public job board
        $this->get('/jobs')->assertOk()->assertDontSee('Draft Position');
        $this->get("/jobs/{$job->id}")->assertNotFound();

        // Admin publishes it
        $this->actingAs($admin)->patch("/admin/jobs/{$job->id}/publish")->assertRedirect();

        $this->assertEquals('published', $job->fresh()->status);
        $this->get('/jobs')->assertOk()->assertSee('Draft Position');
    }

    public function test_non_admin_cannot_access_admin_job_routes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/jobs/create')->assertForbidden();
        $this->actingAs($user)->post('/admin/jobs', [
            'title' => 'Sneaky Job',
            'company' => 'Evil Corp',
            'description' => 'Hack.',
            'employment_type' => 'Full Time',
        ])->assertForbidden();
    }
}
