<?php

namespace Tests\Feature;

use App\Mail\AlumniOtpMail;
use App\Mail\PasswordResetOtpMail;
use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Forgot password page loads.
     */
    public function test_forgot_password_page_loads(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertOk();
        $response->assertSee('Reset your password');
        $response->assertSee('Send Verification Code');
        // Ensure old placeholder text is completely removed
        $response->assertDontSee('Password reset via email OTP is not yet active');
    }

    /**
     * 2. Registered email can request an OTP.
     */
    public function test_registered_email_can_request_otp(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'registered@example.com']);

        $response = $this->post('/forgot-password', [
            'email' => 'registered@example.com',
        ]);

        $response->assertRedirect(route('password.otp'));
        $response->assertSessionHas('password_reset.email', 'registered@example.com');
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('otp_verifications', [
            'email' => 'registered@example.com',
            'purpose' => 'password_reset',
            'is_verified' => false,
        ]);
    }

    /**
     * 3. OTP email is sent successfully using Mail::fake().
     */
    public function test_otp_email_is_sent_successfully_using_mail_fake(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'student@example.com']);

        $this->post('/forgot-password', ['email' => 'student@example.com']);

        Mail::assertSent(PasswordResetOtpMail::class, function ($mail) {
            return $mail->hasTo('student@example.com')
                && $mail->hasSubject('BUP ICT Alumni Association - Password Reset Verification')
                && strlen($mail->code) === 6
                && $mail->expiryMinutes === 5;
        });
    }

    /**
     * 4. Unregistered email receives a generic response.
     */
    public function test_unregistered_email_receives_generic_response(): void
    {
        Mail::fake();

        $response = $this->post('/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        // Generic redirect with same status message to avoid email enumeration
        $response->assertRedirect(route('password.otp'));
        $response->assertSessionHas('status', 'If an account exists with that email address, a 6-digit verification code has been sent.');

        // No mail dispatched
        Mail::assertNothingSent();

        // No OTP generated in database
        $this->assertDatabaseMissing('otp_verifications', [
            'email' => 'nonexistent@example.com',
            'purpose' => 'password_reset',
        ]);
    }

    /**
     * 5. Correct OTP is accepted.
     */
    public function test_correct_otp_is_accepted(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'alumni@example.com']);
        $this->post('/forgot-password', ['email' => 'alumni@example.com']);

        $capturedCode = null;
        Mail::assertSent(PasswordResetOtpMail::class, function ($mail) use (&$capturedCode) {
            $capturedCode = $mail->code;

            return true;
        });

        $this->assertNotNull($capturedCode);

        $response = $this->post('/forgot-password/verify-otp', [
            'otp_code' => $capturedCode,
        ]);

        $this->assertTrue(session()->has('password_reset_auth'));
        $resetToken = session('password_reset_auth.token');
        $this->assertNotEmpty($resetToken);

        $response->assertRedirect(route('password.reset', ['token' => $resetToken]));

        $this->assertDatabaseHas('otp_verifications', [
            'email' => 'alumni@example.com',
            'purpose' => 'password_reset',
            'is_verified' => true,
        ]);
    }

    /**
     * 6. Incorrect OTP is rejected.
     */
    public function test_incorrect_otp_is_rejected(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'alumni@example.com']);
        $this->post('/forgot-password', ['email' => 'alumni@example.com']);

        $response = $this->post('/forgot-password/verify-otp', [
            'otp_code' => '999999',
        ]);

        $response->assertSessionHasErrors('otp_code');
        $this->assertFalse(session()->has('password_reset_auth'));

        $this->assertDatabaseHas('otp_verifications', [
            'email' => 'alumni@example.com',
            'purpose' => 'password_reset',
            'is_verified' => false,
            'attempts' => 1,
        ]);
    }

    /**
     * 7. Expired OTP is rejected.
     */
    public function test_expired_otp_is_rejected(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'alumni@example.com']);
        $this->post('/forgot-password', ['email' => 'alumni@example.com']);

        $capturedCode = null;
        Mail::assertSent(PasswordResetOtpMail::class, function ($mail) use (&$capturedCode) {
            $capturedCode = $mail->code;

            return true;
        });

        // Fast forward 6 minutes (beyond 5-minute expiry)
        Carbon::setTestNow(Carbon::now()->addMinutes(6));

        $response = $this->post('/forgot-password/verify-otp', [
            'otp_code' => $capturedCode,
        ]);

        $response->assertSessionHasErrors('otp_code');
        $this->assertFalse(session()->has('password_reset_auth'));

        Carbon::setTestNow(); // Reset time mock
    }

    /**
     * 8. OTP cannot be reused.
     */
    public function test_otp_cannot_be_reused(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'alumni@example.com']);
        $this->post('/forgot-password', ['email' => 'alumni@example.com']);

        $capturedCode = null;
        Mail::assertSent(PasswordResetOtpMail::class, function ($mail) use (&$capturedCode) {
            $capturedCode = $mail->code;

            return true;
        });

        // First verification succeeds
        $this->post('/forgot-password/verify-otp', ['otp_code' => $capturedCode])
            ->assertRedirect();

        // Attempting to verify the same code again fails
        $response = $this->post('/forgot-password/verify-otp', ['otp_code' => $capturedCode]);
        $response->assertSessionHasErrors('otp_code');
    }

    /**
     * 9. More than 5 incorrect attempts are blocked.
     */
    public function test_more_than_five_incorrect_attempts_are_blocked(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'brute@example.com']);
        $this->post('/forgot-password', ['email' => 'brute@example.com']);

        $capturedCode = null;
        Mail::assertSent(PasswordResetOtpMail::class, function ($mail) use (&$capturedCode) {
            $capturedCode = $mail->code;

            return true;
        });

        // Fail 5 times
        for ($i = 0; $i < 5; $i++) {
            $this->post('/forgot-password/verify-otp', ['otp_code' => '000000'])
                ->assertSessionHasErrors('otp_code');
        }

        // 6th attempt with the CORRECT code must now be blocked
        $response = $this->post('/forgot-password/verify-otp', ['otp_code' => $capturedCode]);
        $response->assertSessionHasErrors('otp_code');
        $this->assertFalse(session()->has('password_reset_auth'));
    }

    /**
     * 10. Resend cooldown works.
     */
    public function test_resend_cooldown_works(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'cooldown@example.com']);
        $this->post('/forgot-password', ['email' => 'cooldown@example.com']);

        // Immediately attempt to resend (within 60 seconds)
        $response = $this->post('/forgot-password/resend-otp');
        $response->assertSessionHasErrors('otp_code');

        // Only 1 mail should have been sent (from the initial request)
        Mail::assertSent(PasswordResetOtpMail::class, 1);

        // Fast forward 61 seconds
        Carbon::setTestNow(Carbon::now()->addSeconds(61));

        $resendResponse = $this->post('/forgot-password/resend-otp');
        $resendResponse->assertSessionHas('status');

        // A second mail is now sent
        Mail::assertSent(PasswordResetOtpMail::class, 2);

        Carbon::setTestNow();
    }

    /**
     * 11. Previous OTP becomes invalid after resend.
     */
    public function test_previous_otp_becomes_invalid_after_resend(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'resendtest@example.com']);
        $this->post('/forgot-password', ['email' => 'resendtest@example.com']);

        $firstCode = null;
        Mail::assertSent(PasswordResetOtpMail::class, function ($mail) use (&$firstCode) {
            $firstCode = $mail->code;

            return true;
        });

        // Fast-forward cooldown and resend
        Carbon::setTestNow(Carbon::now()->addSeconds(61));
        $this->post('/forgot-password/resend-otp');

        $secondCode = null;
        Mail::assertSent(PasswordResetOtpMail::class, function ($mail) use (&$secondCode, $firstCode) {
            if ($mail->code !== $firstCode) {
                $secondCode = $mail->code;
            }

            return true;
        });

        $this->assertNotNull($secondCode);
        $this->assertNotEquals($firstCode, $secondCode);

        // Attempting to verify the first OTP must fail
        $response = $this->post('/forgot-password/verify-otp', ['otp_code' => $firstCode]);
        $response->assertSessionHasErrors('otp_code');

        // Verifying the second OTP must succeed
        $successResponse = $this->post('/forgot-password/verify-otp', ['otp_code' => $secondCode]);
        $successResponse->assertRedirect();
        $this->assertTrue(session()->has('password_reset_auth'));

        Carbon::setTestNow();
    }

    /**
     * 12. Password reset cannot happen before OTP verification.
     */
    public function test_password_reset_cannot_happen_before_otp_verification(): void
    {
        // Visiting the reset password form without OTP authorization redirects back
        $response = $this->get('/reset-password/unauthorized-token');
        $response->assertRedirect(route('password.request'));
        $response->assertSessionHasErrors('email');

        // Submitting a password reset without OTP authorization is rejected
        $postResponse = $this->post('/reset-password', [
            'token' => 'fake-token',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $postResponse->assertRedirect(route('password.request'));
        $postResponse->assertSessionHasErrors('email');
    }

    /**
     * 13. Password and confirmation mismatch is rejected.
     */
    public function test_password_and_confirmation_mismatch_is_rejected(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'mismatch@example.com', 'password' => Hash::make('oldpassword123')]);
        $this->post('/forgot-password', ['email' => 'mismatch@example.com']);

        $capturedCode = null;
        Mail::assertSent(PasswordResetOtpMail::class, function ($mail) use (&$capturedCode) {
            $capturedCode = $mail->code;

            return true;
        });

        $this->post('/forgot-password/verify-otp', ['otp_code' => $capturedCode]);
        $token = session('password_reset_auth.token');

        $response = $this->post('/reset-password', [
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword123',
        ]);

        $response->assertSessionHasErrors('password');

        // Ensure old password is untouched
        $user->refresh();
        $this->assertTrue(Hash::check('oldpassword123', $user->password));
    }

    /**
     * 14. Password is securely hashed.
     */
    public function test_password_is_securely_hashed(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'hashcheck@example.com', 'password' => Hash::make('oldpassword123')]);
        $this->post('/forgot-password', ['email' => 'hashcheck@example.com']);

        $capturedCode = null;
        Mail::assertSent(PasswordResetOtpMail::class, function ($mail) use (&$capturedCode) {
            $capturedCode = $mail->code;

            return true;
        });

        // Verify OTP is hashed in the database (not stored as plaintext)
        $otpRecord = OtpVerification::where('email', 'hashcheck@example.com')
            ->where('purpose', 'password_reset')
            ->firstOrFail();

        $this->assertNotEquals($capturedCode, $otpRecord->otp_code);
        $this->assertTrue(Hash::check($capturedCode, $otpRecord->otp_code));

        $this->post('/forgot-password/verify-otp', ['otp_code' => $capturedCode]);
        $token = session('password_reset_auth.token');

        $this->post('/reset-password', [
            'token' => $token,
            'password' => 'BrandNewPassword123!',
            'password_confirmation' => 'BrandNewPassword123!',
        ]);

        $user->refresh();
        $this->assertNotEquals('BrandNewPassword123!', $user->password);
        $this->assertTrue(Hash::check('BrandNewPassword123!', $user->password));
    }

    /**
     * 15. Successful password reset invalidates the reset authorization.
     */
    public function test_successful_password_reset_invalidates_reset_authorization(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'invalidate@example.com', 'password' => Hash::make('oldpassword123')]);
        $this->post('/forgot-password', ['email' => 'invalidate@example.com']);

        $capturedCode = null;
        Mail::assertSent(PasswordResetOtpMail::class, function ($mail) use (&$capturedCode) {
            $capturedCode = $mail->code;

            return true;
        });

        $this->post('/forgot-password/verify-otp', ['otp_code' => $capturedCode]);
        $token = session('password_reset_auth.token');

        $response = $this->post('/reset-password', [
            'token' => $token,
            'password' => 'firstReset123',
            'password_confirmation' => 'firstReset123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        // Session authorization is cleared
        $this->assertFalse(session()->has('password_reset_auth'));
        $this->assertFalse(session()->has('password_reset'));

        // All password_reset OTPs for this email are invalidated/deleted
        $this->assertDatabaseMissing('otp_verifications', [
            'email' => 'invalidate@example.com',
            'purpose' => 'password_reset',
        ]);

        // Attempting to post to /reset-password again with the same token is rejected
        $secondPost = $this->post('/reset-password', [
            'token' => $token,
            'password' => 'secondReset123',
            'password_confirmation' => 'secondReset123',
        ]);

        $secondPost->assertRedirect(route('password.request'));
    }

    /**
     * 16. User can log in with the new password.
     */
    public function test_user_can_log_in_with_new_password(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'loginuser@example.com',
            'password' => Hash::make('originalPassword123'),
        ]);

        $this->post('/forgot-password', ['email' => 'loginuser@example.com']);

        $capturedCode = null;
        Mail::assertSent(PasswordResetOtpMail::class, function ($mail) use (&$capturedCode) {
            $capturedCode = $mail->code;

            return true;
        });

        $this->post('/forgot-password/verify-otp', ['otp_code' => $capturedCode]);
        $token = session('password_reset_auth.token');

        $this->post('/reset-password', [
            'token' => $token,
            'password' => 'superSecretNewPassword123',
            'password_confirmation' => 'superSecretNewPassword123',
        ]);

        // Old password no longer works
        $failedLogin = $this->post('/login', [
            'email' => 'loginuser@example.com',
            'password' => 'originalPassword123',
        ]);
        $failedLogin->assertSessionHasErrors('email');
        $this->assertGuest();

        // New password logs in successfully
        $successLogin = $this->post('/login', [
            'email' => 'loginuser@example.com',
            'password' => 'superSecretNewPassword123',
        ]);

        $successLogin->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * 17. Registration OTP cannot be used for password recovery.
     */
    public function test_registration_otp_cannot_be_used_for_password_recovery(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'crossover@example.com']);

        // Issue a registration OTP
        $registrationOtp = OtpVerification::create([
            'email' => 'crossover@example.com',
            'otp_code' => '777888',
            'purpose' => 'registration',
            'expires_at' => Carbon::now()->addMinutes(10),
            'is_verified' => false,
            'attempts' => 0,
        ]);

        // Initiate a password reset session
        $this->post('/forgot-password', ['email' => 'crossover@example.com']);

        // Attempt to verify password reset using the registration OTP code
        $response = $this->post('/forgot-password/verify-otp', [
            'otp_code' => '777888',
        ]);

        $response->assertSessionHasErrors('otp_code');
        $this->assertFalse(session()->has('password_reset_auth'));

        // The registration OTP must remain untouched and unverified
        $registrationOtp->refresh();
        $this->assertFalse($registrationOtp->is_verified);
    }

    /**
     * 18. Unauthorized users cannot access another user's reset session.
     */
    public function test_unauthorized_users_cannot_access_another_users_reset_session(): void
    {
        Mail::fake();

        $victim = User::factory()->create(['email' => 'victim@example.com']);

        // Victim requests and verifies OTP
        $this->post('/forgot-password', ['email' => 'victim@example.com']);

        $capturedCode = null;
        Mail::assertSent(PasswordResetOtpMail::class, function ($mail) use (&$capturedCode) {
            $capturedCode = $mail->code;

            return true;
        });

        $this->post('/forgot-password/verify-otp', ['otp_code' => $capturedCode]);
        $victimToken = session('password_reset_auth.token');

        // Now simulate another visitor (different session) attempting to use victimToken
        // Flush session to simulate an attacker in a separate browser/session
        $this->flushSession();

        $attackerVisit = $this->get('/reset-password/' . $victimToken);
        $attackerVisit->assertRedirect(route('password.request'));
        $attackerVisit->assertSessionHasErrors('email');

        $attackerSubmit = $this->post('/reset-password', [
            'token' => $victimToken,
            'password' => 'attackerPassword123',
            'password_confirmation' => 'attackerPassword123',
        ]);

        $attackerSubmit->assertRedirect(route('password.request'));
        $attackerSubmit->assertSessionHasErrors('email');

        // Victim's password remains unchanged
        $victim->refresh();
        $this->assertFalse(Hash::check('attackerPassword123', $victim->password));
    }
}
