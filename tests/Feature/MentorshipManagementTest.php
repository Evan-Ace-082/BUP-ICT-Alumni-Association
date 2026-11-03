<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\MentorshipRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MentorshipManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function alumniUser(bool $mentor = false): array
    {
        $user = User::factory()->create();
        $alumni = Alumni::factory()->active()->create(['user_id' => $user->id, 'mentorship' => $mentor]);

        return [$user, $alumni];
    }

    public function test_the_directory_shows_available_mentors(): void
    {
        [$viewer] = $this->alumniUser();
        [, $mentorAlumni] = $this->alumniUser(mentor: true);

        $this->actingAs($viewer)->get('/dashboard/mentorship')->assertOk()->assertSee($mentorAlumni->full_name);
    }

    public function test_the_directory_does_not_show_non_mentors(): void
    {
        [$viewer] = $this->alumniUser();
        [, $nonMentor] = $this->alumniUser(mentor: false);

        $this->actingAs($viewer)->get('/dashboard/mentorship')->assertOk()->assertDontSee($nonMentor->full_name);
    }

    public function test_an_alumnus_can_send_a_mentorship_request(): void
    {
        [$mentee] = $this->alumniUser();
        [$mentorUser, $mentorAlumni] = $this->alumniUser(mentor: true);

        $this->actingAs($mentee)->post("/dashboard/mentorship/{$mentorAlumni->id}/request", ['message' => 'Please mentor me'])
            ->assertRedirect();

        $this->assertDatabaseHas('mentorship_requests', [
            'mentor_id' => $mentorUser->id, 'mentee_id' => $mentee->id, 'status' => 'pending',
        ]);
    }

    public function test_a_request_starts_pending(): void
    {
        [$mentee] = $this->alumniUser();
        [, $mentorAlumni] = $this->alumniUser(mentor: true);

        $this->actingAs($mentee)->post("/dashboard/mentorship/{$mentorAlumni->id}/request");

        $this->assertDatabaseHas('mentorship_requests', ['mentee_id' => $mentee->id, 'status' => MentorshipRequest::STATUS_PENDING]);
    }

    public function test_a_mentor_can_accept_a_request(): void
    {
        [$mentorUser] = $this->alumniUser(mentor: true);
        [$mentee] = $this->alumniUser();
        $request = MentorshipRequest::factory()->create(['mentor_id' => $mentorUser->id, 'mentee_id' => $mentee->id]);

        $this->actingAs($mentorUser)->patch("/dashboard/mentorship/requests/{$request->id}/accept")->assertRedirect();

        $this->assertDatabaseHas('mentorship_requests', ['id' => $request->id, 'status' => 'accepted']);
    }

    public function test_a_mentor_can_reject_a_request(): void
    {
        [$mentorUser] = $this->alumniUser(mentor: true);
        [$mentee] = $this->alumniUser();
        $request = MentorshipRequest::factory()->create(['mentor_id' => $mentorUser->id, 'mentee_id' => $mentee->id]);

        $this->actingAs($mentorUser)->patch("/dashboard/mentorship/requests/{$request->id}/reject")->assertRedirect();

        $this->assertDatabaseHas('mentorship_requests', ['id' => $request->id, 'status' => 'rejected']);
    }

    public function test_a_mentee_cannot_accept_their_own_sent_request(): void
    {
        [$mentorUser] = $this->alumniUser(mentor: true);
        [$mentee] = $this->alumniUser();
        $request = MentorshipRequest::factory()->create(['mentor_id' => $mentorUser->id, 'mentee_id' => $mentee->id]);

        $this->actingAs($mentee)->patch("/dashboard/mentorship/requests/{$request->id}/accept")->assertForbidden();

        $this->assertDatabaseHas('mentorship_requests', ['id' => $request->id, 'status' => 'pending']);
    }

    public function test_an_unrelated_user_cannot_modify_someone_elses_request(): void
    {
        [$mentorUser] = $this->alumniUser(mentor: true);
        [$mentee] = $this->alumniUser();
        [$stranger] = $this->alumniUser();
        $request = MentorshipRequest::factory()->create(['mentor_id' => $mentorUser->id, 'mentee_id' => $mentee->id]);

        $this->actingAs($stranger)->patch("/dashboard/mentorship/requests/{$request->id}/accept")->assertForbidden();
        $this->actingAs($stranger)->patch("/dashboard/mentorship/requests/{$request->id}/reject")->assertForbidden();
        $this->actingAs($stranger)->patch("/dashboard/mentorship/requests/{$request->id}/cancel")->assertForbidden();
    }

    public function test_mentor_and_mentee_ids_cannot_be_spoofed_via_the_request_body(): void
    {
        [$mentee] = $this->alumniUser();
        [$realMentorUser, $realMentorAlumni] = $this->alumniUser(mentor: true);
        $victim = User::factory()->create();

        // The route parameter names the real mentor; a spoofed
        // mentor_id/mentee_id in the POST body must be ignored, since
        // the controller never reads those keys from the request.
        $this->actingAs($mentee)->post("/dashboard/mentorship/{$realMentorAlumni->id}/request", [
            'mentor_id' => $victim->id,
            'mentee_id' => $victim->id,
            'message' => 'test',
        ]);

        $this->assertDatabaseHas('mentorship_requests', [
            'mentor_id' => $realMentorUser->id,
            'mentee_id' => $mentee->id,
        ]);
        $this->assertDatabaseMissing('mentorship_requests', ['mentor_id' => $victim->id]);
    }

    public function test_a_user_cannot_request_mentorship_from_themself(): void
    {
        [$user, $alumni] = $this->alumniUser(mentor: true);

        $this->actingAs($user)->post("/dashboard/mentorship/{$alumni->id}/request")->assertStatus(422);
    }

    public function test_a_duplicate_pending_request_to_the_same_mentor_is_rejected(): void
    {
        [$mentee] = $this->alumniUser();
        [, $mentorAlumni] = $this->alumniUser(mentor: true);

        $this->actingAs($mentee)->post("/dashboard/mentorship/{$mentorAlumni->id}/request");
        $response = $this->actingAs($mentee)->post("/dashboard/mentorship/{$mentorAlumni->id}/request");

        $response->assertStatus(422);
        $this->assertEquals(1, MentorshipRequest::count());
    }

    public function test_a_mentee_can_cancel_their_own_pending_request(): void
    {
        [$mentorUser] = $this->alumniUser(mentor: true);
        [$mentee] = $this->alumniUser();
        $request = MentorshipRequest::factory()->create(['mentor_id' => $mentorUser->id, 'mentee_id' => $mentee->id]);

        $this->actingAs($mentee)->patch("/dashboard/mentorship/requests/{$request->id}/cancel")->assertRedirect();

        $this->assertDatabaseHas('mentorship_requests', ['id' => $request->id, 'status' => 'cancelled']);
    }
}
