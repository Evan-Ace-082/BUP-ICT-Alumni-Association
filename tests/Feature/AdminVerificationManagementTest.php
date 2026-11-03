<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Batch;
use App\Models\Role;
use App\Models\User;
use App\Models\VerificationRequest;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminVerificationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $plainAlumni;

    protected Batch $batch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->batch = Batch::create([
            'batch_number' => 1,
            'batch_name' => 'Batch 01',
            'batch_code' => 'BICE-01',
            'session' => '2015-2016',
            'passing_year' => 2019,
        ]);

        $this->admin = User::factory()->create();
        $adminRole = Role::where('slug', 'admin')->first();
        $this->admin->roles()->attach($adminRole);

        $this->plainAlumni = User::factory()->create();
        $alumniRole = Role::where('slug', 'alumni')->first();
        $this->plainAlumni->roles()->attach($alumniRole);
    }

    public function test_pending_requests_appear_in_admin_verification_requests_index(): void
    {
        $request = VerificationRequest::create([
            'full_name' => 'Tareq Rahman',
            'email' => 'tareq@example.com',
            'student_id' => '160101',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.verifications.index'));

        $response->assertStatus(200);
        $response->assertSee('Tareq Rahman');
        $response->assertSee('160101');
        $response->assertSee('tareq@example.com');
    }

    public function test_admin_can_open_and_review_a_verification_request(): void
    {
        $request = VerificationRequest::create([
            'full_name' => 'Sultana Razia',
            'email' => 'razia@example.com',
            'student_id' => '160102',
            'message' => 'Please verify my account',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.verifications.show', $request));

        $response->assertStatus(200);
        $response->assertSee('Sultana Razia');
        $response->assertSee('160102');
        $response->assertSee('Please verify my account');
        $response->assertSee('Approve &amp; Verify Alumni', false);
    }

    public function test_valid_approval_creates_official_alumni_record_and_marks_request_approved(): void
    {
        $request = VerificationRequest::create([
            'full_name' => 'Fahim Hasan',
            'email' => 'fahim@example.com',
            'student_id' => '160103',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.verifications.approve', $request), [
            'batch_id' => $this->batch->id,
            'student_id' => '160103',
        ]);

        $response->assertRedirect(route('admin.verifications.index'));
        $this->assertDatabaseHas('verification_requests', [
            'id' => $request->id,
            'status' => 'approved',
            'batch_id' => $this->batch->id,
            'reviewed_by' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('alumni', [
            'student_id' => '160103',
            'full_name' => 'Fahim Hasan',
            'email' => 'fahim@example.com',
            'batch_id' => $this->batch->id,
            'status' => Alumni::STATUS_UNCLAIMED,
        ]);
    }

    public function test_approval_links_existing_user_account_and_assigns_alumni_role(): void
    {
        $user = User::factory()->create([
            'email' => 'existinguser@example.com',
            'name' => 'Existing User',
        ]);

        $request = VerificationRequest::create([
            'full_name' => 'Existing User',
            'email' => 'existinguser@example.com',
            'student_id' => '160104',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.verifications.approve', $request), [
            'batch_id' => $this->batch->id,
            'student_id' => '160104',
        ]);

        $response->assertRedirect(route('admin.verifications.index'));

        $this->assertDatabaseHas('alumni', [
            'student_id' => '160104',
            'user_id' => $user->id,
            'status' => Alumni::STATUS_ACTIVE,
        ]);

        $this->assertTrue($user->fresh()->hasRole('alumni'));
    }

    public function test_rejection_marks_request_rejected_and_provides_clear_status(): void
    {
        $request = VerificationRequest::create([
            'full_name' => 'Fake Person',
            'email' => 'fake@example.com',
            'student_id' => '999999',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.verifications.reject', $request));

        $response->assertRedirect(route('admin.verifications.index'));
        $this->assertDatabaseHas('verification_requests', [
            'id' => $request->id,
            'status' => 'rejected',
            'reviewed_by' => $this->admin->id,
        ]);

        // No alumni created
        $this->assertDatabaseMissing('alumni', ['email' => 'fake@example.com']);
    }

    public function test_duplicate_approval_is_prevented_and_does_not_create_duplicate_records(): void
    {
        $request = VerificationRequest::create([
            'full_name' => 'Duplicate Test',
            'email' => 'dup@example.com',
            'student_id' => '160105',
            'status' => 'approved',
        ]);

        Alumni::create([
            'student_id' => '160105',
            'full_name' => 'Duplicate Test',
            'email' => 'dup@example.com',
            'batch_id' => $this->batch->id,
            'status' => Alumni::STATUS_UNCLAIMED,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.verifications.approve', $request), [
            'batch_id' => $this->batch->id,
            'student_id' => '160105',
        ]);

        $response->assertSessionHasErrors('error');
        $this->assertEquals(1, Alumni::where('student_id', '160105')->count());
    }

    public function test_unauthorized_users_cannot_access_or_approve_requests(): void
    {
        $request = VerificationRequest::create([
            'full_name' => 'Protected Request',
            'email' => 'protected@example.com',
            'student_id' => '160106',
            'status' => 'pending',
        ]);

        // Guest cannot access
        $this->get(route('admin.verifications.index'))->assertRedirect(route('login'));
        $this->post(route('admin.verifications.approve', $request), [
            'batch_id' => $this->batch->id,
            'student_id' => '160106',
        ])->assertRedirect(route('login'));

        // Plain alumni cannot access
        $this->actingAs($this->plainAlumni)->get(route('admin.verifications.index'))->assertStatus(403);
        $this->actingAs($this->plainAlumni)->post(route('admin.verifications.approve', $request), [
            'batch_id' => $this->batch->id,
            'student_id' => '160106',
        ])->assertStatus(403);

        $this->assertEquals('pending', $request->fresh()->status);
    }

    public function test_admin_cannot_approve_their_own_verification_request(): void
    {
        $request = VerificationRequest::create([
            'full_name' => $this->admin->name,
            'email' => $this->admin->email,
            'student_id' => '160107',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.verifications.approve', $request), [
            'batch_id' => $this->batch->id,
            'student_id' => '160107',
        ]);

        $response->assertSessionHasErrors('error');
        $this->assertEquals('pending', $request->fresh()->status);
    }
}
