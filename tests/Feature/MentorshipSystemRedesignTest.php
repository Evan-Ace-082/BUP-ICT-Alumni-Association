<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Batch;
use App\Models\MentorProfile;
use App\Models\MentorshipCategory;
use App\Models\MentorshipEligibilityOverride;
use App\Models\MentorshipRequest;
use App\Models\MentorshipSetting;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\MentorshipEligibilityService;
use Database\Seeders\MentorshipCategorySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MentorshipSystemRedesignTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(MentorshipCategorySeeder::class);

        $this->adminUser = User::factory()->create();
        $adminRole = Role::where('slug', 'admin')->first();
        $this->adminUser->roles()->sync([$adminRole->id]);

        MentorshipSetting::current()->update([
            'cohort_mode' => MentorshipSetting::MODE_MANUAL,
            'latest_graduation_year' => 2024,
            'mentorship_enabled' => true,
        ]);
    }

    protected function createVerifiedAlumni(int $graduationYear): array
    {
        $user = User::factory()->create();
        $batch = Batch::factory()->create(['graduation_year' => $graduationYear]);
        $alumni = Alumni::factory()->active()->create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'graduation_year' => $graduationYear,
        ]);
        $alumniRole = Role::where('slug', 'alumni')->first();
        $user->roles()->syncWithoutDetaching([$alumniRole->id]);

        return [$user, $alumni];
    }

    protected function createVerifiedStudent(): array
    {
        $user = User::factory()->create();
        $batch = Batch::factory()->create(['graduation_year' => 2026]);
        $student = Student::create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'student_id' => '10214' . rand(100, 999),
            'full_name' => 'Current ICT Student',
            'email' => 'student' . rand(100, 999) . '@student.bup.edu.bd',
            'session' => '2022-2023',
            'enrollment_year' => 2022,
            'expected_graduation_year' => 2026,
            'verification_status' => Student::VERIFICATION_STATUS_VERIFIED,
            'verified_at' => now(),
            'status' => Student::STATUS_ACTIVE,
        ]);
        $studentRole = Role::where('slug', 'student')->first();
        $user->roles()->syncWithoutDetaching([$studentRole->id]);

        return [$user, $student];
    }

    public function test_cohort_detection_distinguishes_mentee_from_senior_alumni(): void
    {
        $service = app(MentorshipEligibilityService::class);

        // 2024 graduate -> Newest cohort -> Mentee
        [$recentUser] = $this->createVerifiedAlumni(2024);
        $recentEligibility = $service->getUserEligibility($recentUser);
        $this->assertTrue($recentEligibility['can_find_mentor']);
        $this->assertFalse($recentEligibility['can_apply_as_mentor']);
        $this->assertEquals('newest_alumni', $recentEligibility['role_type']);

        // 2021 graduate -> Senior cohort -> Potential Mentor
        [$seniorUser] = $this->createVerifiedAlumni(2021);
        $seniorEligibility = $service->getUserEligibility($seniorUser);
        $this->assertFalse($seniorEligibility['can_find_mentor']);
        $this->assertTrue($seniorEligibility['can_apply_as_mentor']);
        $this->assertEquals('senior_alumni', $seniorEligibility['role_type']);

        // Verified current student -> Mentee
        [$studentUser] = $this->createVerifiedStudent();
        $studentEligibility = $service->getUserEligibility($studentUser);
        $this->assertTrue($studentEligibility['can_find_mentor']);
        $this->assertFalse($studentEligibility['can_apply_as_mentor']);
        $this->assertEquals('student', $studentEligibility['role_type']);
    }

    public function test_automatic_cohort_mode_computes_highest_graduated_year(): void
    {
        MentorshipSetting::current()->update([
            'cohort_mode' => MentorshipSetting::MODE_AUTOMATIC,
        ]);

        Batch::factory()->create(['graduation_year' => 2021]);
        Batch::factory()->create(['graduation_year' => 2023]);

        $service = app(MentorshipEligibilityService::class);
        $latestYear = $service->getLatestGraduationYear();

        $this->assertEquals(2023, $latestYear);
    }

    public function test_admin_override_can_force_mentee_or_mentor_status(): void
    {
        $service = app(MentorshipEligibilityService::class);
        [$seniorUser] = $this->createVerifiedAlumni(2019);

        // Naturally seniorUser cannot be mentee
        $this->assertFalse($service->getUserEligibility($seniorUser)['can_find_mentor']);

        // Admin adds override to force_mentee
        MentorshipEligibilityOverride::create([
            'user_id' => $seniorUser->id,
            'override_type' => MentorshipEligibilityOverride::OVERRIDE_FORCE_MENTEE,
            'reason' => 'Career transition guidance needed',
            'admin_id' => $this->adminUser->id,
        ]);

        $overridden = $service->getUserEligibility($seniorUser);
        $this->assertTrue($overridden['can_find_mentor']);
        $this->assertTrue($overridden['override_applied']);
    }

    public function test_student_can_register_and_admin_can_verify(): void
    {
        $batch = Batch::factory()->create();

        $response = $this->post('/register/student', [
            'full_name' => 'Jane Student',
            'email' => 'jane.student@student.bup.edu.bd',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'student_id' => '10214099',
            'batch_id' => $batch->id,
            'session' => '2022-2023',
            'enrollment_year' => 2022,
            'expected_graduation_year' => 2026,
            'phone' => '01700000000',
        ]);

        $response->assertRedirect('/dashboard');

        $student = Student::where('student_id', '10214099')->first();
        $this->assertNotNull($student);
        $this->assertEquals(Student::VERIFICATION_STATUS_PENDING, $student->verification_status);

        // Admin verifies the student
        $this->actingAs($this->adminUser)
            ->patch("/admin/students/{$student->id}/verify", ['admin_notes' => 'Verified ID card'])
            ->assertRedirect();

        $student->refresh();
        $this->assertEquals(Student::VERIFICATION_STATUS_VERIFIED, $student->verification_status);
        $this->assertNotNull($student->verified_at);
    }

    public function test_admin_can_transition_verified_student_to_alumnus(): void
    {
        [$studentUser, $student] = $this->createVerifiedStudent();
        $alumniBatch = Batch::factory()->create(['batch_code' => 'B06']);

        $response = $this->actingAs($this->adminUser)
            ->post("/admin/students/{$student->id}/transition", [
                'batch_id' => $alumniBatch->id,
                'graduation_year' => 2024,
            ]);

        $response->assertRedirect('/admin/students');

        $student->refresh();
        $this->assertEquals(Student::STATUS_GRADUATED, $student->status);

        $alumni = Alumni::where('student_id', $student->student_id)->first();
        $this->assertNotNull($alumni);
        $this->assertEquals($studentUser->id, $alumni->user_id);
        $this->assertEquals(2024, $alumni->graduation_year);
        $this->assertTrue($studentUser->fresh()->hasRole('alumni'));
    }

    public function test_senior_alumni_can_apply_and_admin_can_approve_mentor(): void
    {
        [$seniorUser, $seniorAlumni] = $this->createVerifiedAlumni(2019);
        $categories = MentorshipCategory::take(2)->pluck('id')->toArray();

        $response = $this->actingAs($seniorUser)->post('/dashboard/mentorship/become-a-mentor', [
            'current_designation' => 'Senior Software Engineer',
            'company' => 'Google',
            'sector' => 'Technology',
            'country' => 'Bangladesh',
            'city' => 'Dhaka',
            'years_of_experience' => 5,
            'linkedin_url' => 'https://linkedin.com/in/seniorengineer',
            'bio' => 'Passionate about guiding junior engineers and students.',
            'expertise_areas' => 'Cloud Computing, System Design',
            'topics_offered' => 'System design interviews, Cloud architecture',
            'preferred_mentee_audience' => 'both',
            'mentorship_mode' => 'both',
            'preferred_language' => 'English & Bengali',
            'availability_schedule' => 'Fridays 7PM-9PM',
            'max_mentees' => 4,
            'mentorship_frequency' => 'biweekly',
            'personal_statement' => 'I want to give back to the BUP ICT community by mentoring young talent.',
            'categories' => $categories,
        ]);

        $response->assertRedirect('/dashboard/mentorship/mentor-hub');

        $profile = MentorProfile::where('user_id', $seniorUser->id)->first();
        $this->assertNotNull($profile);
        $this->assertEquals(MentorProfile::STATUS_PENDING, $profile->status);
        $this->assertCount(2, $profile->categories);

        // Admin reviews and approves mentor
        $this->actingAs($this->adminUser)
            ->patch("/admin/mentorship/applications/{$profile->id}/approve", [
                'admin_review_notes' => 'Excellent profile and industry experience.',
            ])
            ->assertRedirect();

        $profile->refresh();
        $this->assertEquals(MentorProfile::STATUS_APPROVED, $profile->status);
        $this->assertNotNull($profile->approved_at);

        // Approved mentor appears in directory
        [$menteeUser] = $this->createVerifiedAlumni(2024);
        $this->actingAs($menteeUser)
            ->get('/dashboard/mentorship')
            ->assertOk()
            ->assertSee('Senior Software Engineer')
            ->assertSee('Google');
    }

    public function test_mentee_can_submit_request_and_mentor_can_accept_and_complete(): void
    {
        [$seniorUser, $seniorAlumni] = $this->createVerifiedAlumni(2019);
        $category = MentorshipCategory::first();

        $mentorProfile = MentorProfile::create([
            'user_id' => $seniorUser->id,
            'alumni_id' => $seniorAlumni->id,
            'current_designation' => 'Lead Data Scientist',
            'company' => 'Amazon',
            'country' => 'Bangladesh',
            'city' => 'Dhaka',
            'years_of_experience' => 6,
            'bio' => 'Mentoring in machine learning and data science careers.',
            'max_mentees' => 2,
            'status' => MentorProfile::STATUS_APPROVED,
            'approved_at' => now(),
            'preferred_mentee_audience' => 'both',
            'mentorship_mode' => 'online',
        ]);
        $mentorProfile->categories()->attach($category->id);

        [$menteeUser] = $this->createVerifiedAlumni(2024);

        // Mentee sends request
        $response = $this->actingAs($menteeUser)->post("/dashboard/mentorship/mentors/{$mentorProfile->id}/request", [
            'category_id' => $category->id,
            'specific_topic' => 'Career transition into Machine Learning',
            'reason' => 'I graduated recently and want to pursue a career in ML.',
            'career_goals' => 'Join a top tech firm as an ML engineer.',
            'preferred_mode' => 'online',
            'preferred_schedule' => 'Weekends',
            'message' => 'Hello Sir, I would love to learn from your journey.',
        ]);

        $response->assertRedirect();

        $request = MentorshipRequest::where('mentor_id', $seniorUser->id)
            ->where('mentee_id', $menteeUser->id)
            ->first();

        $this->assertNotNull($request);
        $this->assertEquals(MentorshipRequest::STATUS_PENDING, $request->status);

        // Mentor accepts request
        $this->actingAs($seniorUser)
            ->patch("/dashboard/mentorship/requests/{$request->id}/accept")
            ->assertRedirect();

        $request->refresh();
        $this->assertEquals(MentorshipRequest::STATUS_ACCEPTED, $request->status);
        $this->assertEquals(1, $mentorProfile->fresh()->active_mentees_count);
        $this->assertEquals(1, $mentorProfile->fresh()->remaining_slots);

        // Mentor completes mentorship cycle
        $this->actingAs($seniorUser)
            ->patch("/dashboard/mentorship/requests/{$request->id}/complete")
            ->assertRedirect();

        $request->refresh();
        $this->assertEquals(MentorshipRequest::STATUS_COMPLETED, $request->status);
        $this->assertNotNull($request->completed_at);
        $this->assertEquals(0, $mentorProfile->fresh()->active_mentees_count);
    }

    public function test_mentor_capacity_blocks_requests_when_full(): void
    {
        [$seniorUser, $seniorAlumni] = $this->createVerifiedAlumni(2018);

        $mentorProfile = MentorProfile::create([
            'user_id' => $seniorUser->id,
            'alumni_id' => $seniorAlumni->id,
            'current_designation' => 'Staff Architect',
            'company' => 'Meta',
            'country' => 'Bangladesh',
            'city' => 'Dhaka',
            'years_of_experience' => 8,
            'bio' => 'High capacity test mentor.',
            'max_mentees' => 1,
            'status' => MentorProfile::STATUS_APPROVED,
            'approved_at' => now(),
            'preferred_mentee_audience' => 'both',
            'mentorship_mode' => 'both',
        ]);

        [$mentee1] = $this->createVerifiedAlumni(2024);
        [$mentee2] = $this->createVerifiedAlumni(2024);

        // Mentee 1 requests and gets accepted
        $req1 = MentorshipRequest::create([
            'mentor_id' => $seniorUser->id,
            'mentee_id' => $mentee1->id,
            'mentor_profile_id' => $mentorProfile->id,
            'status' => MentorshipRequest::STATUS_ACCEPTED,
        ]);

        // Mentor has 0 remaining slots
        $this->assertEquals(0, $mentorProfile->fresh()->remaining_slots);

        // Mentee 2 attempts request -> rejected with 422
        $this->actingAs($mentee2)
            ->post("/dashboard/mentorship/mentors/{$mentorProfile->id}/request", [
                'message' => 'Can I join your mentorship?',
            ])
            ->assertStatus(422);
    }

    public function test_admin_can_manage_categories(): void
    {
        $response = $this->from('/admin/mentorship/categories')
            ->actingAs($this->adminUser)
            ->post('/admin/mentorship/categories', [
                'name' => 'Quantum Computing & Cryptography',
                'description' => 'Exploring post-quantum security and quantum hardware development.',
                'icon' => 'atom',
                'sort_order' => 99,
            ]);

        $response->assertRedirect('/admin/mentorship/categories');

        $category = MentorshipCategory::where('slug', 'quantum-computing-cryptography')->first();
        $this->assertNotNull($category);
        $this->assertEquals('Quantum Computing & Cryptography', $category->name);

        // Admin updates category
        $this->from('/admin/mentorship/categories')
            ->actingAs($this->adminUser)
            ->put("/admin/mentorship/categories/{$category->id}", [
                'name' => 'Quantum Computing and Security',
                'description' => 'Updated description.',
                'sort_order' => 50,
                'is_active' => true,
            ])
            ->assertRedirect('/admin/mentorship/categories');

        $this->assertEquals('Quantum Computing and Security', $category->fresh()->name);
    }
}
