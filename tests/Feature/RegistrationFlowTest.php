<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Batch;
use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_student_id_is_routed_to_verification_request(): void
    {
        $response = $this->post('/register', ['student_id' => 'DOES-NOT-EXIST']);

        $response->assertRedirect(route('verification.create', ['student_id' => 'DOES-NOT-EXIST']));
    }

    public function test_known_student_id_proceeds_to_registration_details(): void
    {
        $alumni = Alumni::factory()->create(['student_id' => '154677565']);

        $response = $this->post('/register', ['student_id' => '154677565']);

        $response->assertRedirect(route('register.details'));
        $this->assertEquals($alumni->id, session('registration.alumni_id'));
    }

    public function test_already_linked_alumni_record_cannot_be_reclaimed(): void
    {
        $alumni = Alumni::factory()->active()->create([
            'student_id' => '154677565',
            'user_id' => User::factory()->create()->id,
        ]);

        $response = $this->post('/register', ['student_id' => '154677565']);

        $response->assertSessionHasErrors('student_id');
    }

    public function test_full_registration_flow_creates_and_links_a_user_with_a_new_email(): void
    {
        Mail::fake();

        $batch = Batch::factory()->create();
        $alumni = Alumni::factory()->create([
            'student_id' => '154677565',
            'batch_id' => $batch->id,
            'email' => 'old-master-email@example.com', // master data, untouched
        ]);

        $this->post('/register', ['student_id' => '154677565'])
            ->assertRedirect(route('register.details'));

        $this->post('/register/details', [
            'email' => 'new-account-email@example.com', // deliberately different from master
            'phone' => '01811111111',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('register.otp'));

        $otp = OtpVerification::where('email', 'new-account-email@example.com')->firstOrFail();

        $this->post('/register/verify-otp', ['otp_code' => $otp->otp_code])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'new-account-email@example.com', 'phone' => '01811111111']);

        $alumni->refresh();
        $this->assertEquals('active', $alumni->status);
        $this->assertNotNull($alumni->user_id);
        // Master email is untouched by the new account email.
        $this->assertEquals('old-master-email@example.com', $alumni->email);

        $this->assertAuthenticated();
    }

    public function test_an_incorrect_otp_does_not_create_an_account(): void
    {
        Mail::fake();

        Alumni::factory()->create(['student_id' => '154677565']);

        $this->post('/register', ['student_id' => '154677565']);
        $this->post('/register/details', [
            'email' => 'someone@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response = $this->post('/register/verify-otp', ['otp_code' => '000000']);

        $response->assertSessionHasErrors('otp_code');
        $this->assertDatabaseMissing('users', ['email' => 'someone@example.com']);
        $this->assertGuest();
    }

    public function test_registration_succeeds_when_the_master_alumni_email_is_null(): void
    {
        Mail::fake();

        // Master record has no email on file at all — the account
        // email submitted at registration is entirely independent.
        $alumni = Alumni::factory()->create([
            'student_id' => '154677565',
            'email' => null,
        ]);

        $this->post('/register', ['student_id' => '154677565'])
            ->assertRedirect(route('register.details'));

        $this->post('/register/details', [
            'email' => 'new-account@example.com',
            'phone' => '01811111111',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('register.otp'));

        $otp = OtpVerification::where('email', 'new-account@example.com')->firstOrFail();

        $this->post('/register/verify-otp', ['otp_code' => $otp->otp_code])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'new-account@example.com',
            'phone' => '01811111111',
        ]);

        $alumni->refresh();
        $this->assertNull($alumni->email); // master email remains NULL, untouched
        $this->assertNotNull($alumni->user_id);
        $this->assertEquals('active', $alumni->status);

        $user = User::where('email', 'new-account@example.com')->firstOrFail();
        $this->assertTrue($alumni->user->is($user));

        $this->assertAuthenticated();
    }

    public function test_otp_mail_is_sent_to_submitted_email(): void
    {
        Mail::fake();

        $alumni = Alumni::factory()->create(['student_id' => '154677565']);

        $this->post('/register', ['student_id' => '154677565']);

        $this->post('/register/details', [
            'email' => 'student.otp@example.com',
            'phone' => '01811111111',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('register.otp'));

        Mail::assertSent(\App\Mail\AlumniOtpMail::class, function ($mail) {
            return $mail->hasTo('student.otp@example.com')
                && strlen($mail->code) === 6;
        });
    }

    public function test_mail_transport_failure_does_not_falsely_report_success(): void
    {
        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new \RuntimeException('SMTP connection timed out'));

        $alumni = Alumni::factory()->create(['student_id' => '154677565']);

        $this->post('/register', ['student_id' => '154677565']);

        $response = $this->from('/register/details')->post('/register/details', [
            'email' => 'failed.mail@example.com',
            'phone' => '01811111111',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/register/details');
        $response->assertSessionHasErrors('email');
        $this->assertNull(session('registration.draft'));
        $this->assertDatabaseMissing('otp_verifications', ['email' => 'failed.mail@example.com']);
    }

    public function test_expired_otp_cannot_be_verified(): void
    {
        Mail::fake();

        $alumni = Alumni::factory()->create(['student_id' => '154677565']);

        $this->post('/register', ['student_id' => '154677565']);
        $this->post('/register/details', [
            'email' => 'expired@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $otp = OtpVerification::where('email', 'expired@example.com')->firstOrFail();
        $otp->update(['expires_at' => now()->subMinutes(5)]);

        $response = $this->post('/register/verify-otp', ['otp_code' => $otp->otp_code]);

        $response->assertSessionHasErrors('otp_code');
        $this->assertDatabaseMissing('users', ['email' => 'expired@example.com']);
        $this->assertGuest();
    }

    public function test_reused_otp_cannot_be_verified(): void
    {
        Mail::fake();

        $alumni = Alumni::factory()->create(['student_id' => '154677565']);

        $this->post('/register', ['student_id' => '154677565']);
        $this->post('/register/details', [
            'email' => 'reused@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $otp = OtpVerification::where('email', 'reused@example.com')->firstOrFail();

        // Mark OTP as already verified (e.g. from prior verification)
        $otp->update(['is_verified' => true]);

        $response = $this->post('/register/verify-otp', ['otp_code' => $otp->otp_code]);

        $response->assertSessionHasErrors('otp_code');
        $this->assertDatabaseMissing('users', ['email' => 'reused@example.com']);
        $this->assertGuest();
    }

    public function test_resend_otp_issues_fresh_code_and_sends_email(): void
    {
        Mail::fake();

        $alumni = Alumni::factory()->create(['student_id' => '154677565']);

        $this->post('/register', ['student_id' => '154677565']);
        $this->post('/register/details', [
            'email' => 'resend@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $firstOtp = OtpVerification::where('email', 'resend@example.com')->firstOrFail();

        $response = $this->post('/register/resend-otp');

        $response->assertSessionHas('status');

        $latestOtp = OtpVerification::where('email', 'resend@example.com')->firstOrFail();
        // The service invalidates older unverified OTPs and issues a fresh one
        $this->assertDatabaseCount('otp_verifications', 1);

        Mail::assertSent(\App\Mail\AlumniOtpMail::class, 2);
    }

    public function test_resend_otp_handles_mail_failure_gracefully(): void
    {
        Mail::fake();

        $alumni = Alumni::factory()->create(['student_id' => '154677565']);

        $this->post('/register', ['student_id' => '154677565']);
        $this->post('/register/details', [
            'email' => 'resend.fail@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Next call will fail
        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new \RuntimeException('Transport connection error'));

        $response = $this->post('/register/resend-otp');

        $response->assertSessionHasErrors('otp_code');
        $response->assertSessionMissing('status');
    }
}
