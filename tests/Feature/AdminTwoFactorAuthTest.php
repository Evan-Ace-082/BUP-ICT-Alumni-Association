<?php

namespace Tests\Feature;

use App\Mail\AdminLoginOtpMail;
use App\Models\OtpVerification;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminTwoFactorAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function userWithRole(string $slug, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'password' => 'password123',
        ], $attributes));

        $user->roles()->attach(Role::where('slug', $slug)->firstOrFail());

        return $user;
    }

    public function test_admin_credentials_prompt_for_2fa_and_dispatch_otp_email(): void
    {
        Mail::fake();

        $admin = $this->userWithRole('admin');

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.login.otp'));
        $this->assertGuest();
        $this->assertEquals($admin->id, session('admin_2fa.user_id'));

        Mail::assertSent(AdminLoginOtpMail::class, function ($mail) use ($admin) {
            return $mail->hasTo($admin->email) && strlen($mail->code) === 6;
        });
    }

    public function test_super_admin_credentials_prompt_for_2fa_and_dispatch_otp_email(): void
    {
        Mail::fake();

        $superAdmin = $this->userWithRole('super-admin');

        $response = $this->post('/login', [
            'email' => $superAdmin->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.login.otp'));
        $this->assertGuest();
        $this->assertEquals($superAdmin->id, session('admin_2fa.user_id'));

        Mail::assertSent(AdminLoginOtpMail::class, function ($mail) use ($superAdmin) {
            return $mail->hasTo($superAdmin->email) && strlen($mail->code) === 6;
        });
    }

    public function test_admin_cannot_access_protected_admin_routes_before_otp_verification(): void
    {
        Mail::fake();

        $admin = $this->userWithRole('admin');

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        // Attempting to visit /admin before OTP verification must fail and redirect to login
        $response = $this->get('/admin');
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_with_wrong_password_does_not_send_otp_and_fails_login(): void
    {
        Mail::fake();

        $admin = $this->userWithRole('admin');

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertNull(session('admin_2fa'));
        Mail::assertNothingSent();
    }

    public function test_suspended_admin_cannot_initiate_2fa_or_log_in(): void
    {
        Mail::fake();

        $admin = $this->userWithRole('admin', ['status' => User::STATUS_SUSPENDED]);

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertNull(session('admin_2fa'));
        Mail::assertNothingSent();
    }

    public function test_correct_otp_completes_admin_login_and_grants_access(): void
    {
        Mail::fake();

        $admin = $this->userWithRole('admin');

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $otp = OtpVerification::where('email', $admin->email)
            ->where('purpose', 'admin_login')
            ->firstOrFail();

        $response = $this->post('/admin/login/verify-otp', [
            'otp_code' => $otp->otp_code,
        ]);

        $response->assertRedirect(route('admin.index'));
        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session('admin_2fa'));

        // Now authorized to access admin panel
        $this->get('/admin')->assertOk();
    }

    public function test_incorrect_otp_does_not_authenticate_admin(): void
    {
        Mail::fake();

        $admin = $this->userWithRole('admin');

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $response = $this->post('/admin/login/verify-otp', [
            'otp_code' => '000000',
        ]);

        $response->assertSessionHasErrors('otp_code');
        $this->assertGuest();
    }

    public function test_expired_otp_cannot_be_verified(): void
    {
        Mail::fake();

        $admin = $this->userWithRole('admin');

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $otp = OtpVerification::where('email', $admin->email)
            ->where('purpose', 'admin_login')
            ->firstOrFail();

        $otp->update(['expires_at' => now()->subMinutes(5)]);

        $response = $this->post('/admin/login/verify-otp', [
            'otp_code' => $otp->otp_code,
        ]);

        $response->assertSessionHasErrors('otp_code');
        $this->assertGuest();
    }

    public function test_reused_otp_cannot_be_verified(): void
    {
        Mail::fake();

        $admin = $this->userWithRole('admin');

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $otp = OtpVerification::where('email', $admin->email)
            ->where('purpose', 'admin_login')
            ->firstOrFail();

        // Mark as verified
        $otp->update(['is_verified' => true]);

        $response = $this->post('/admin/login/verify-otp', [
            'otp_code' => $otp->otp_code,
        ]);

        $response->assertSessionHasErrors('otp_code');
        $this->assertGuest();
    }

    public function test_brute_force_protection_blocks_after_max_attempts(): void
    {
        Mail::fake();

        $admin = $this->userWithRole('admin');

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $otp = OtpVerification::where('email', $admin->email)
            ->where('purpose', 'admin_login')
            ->firstOrFail();

        // Simulate 5 incorrect attempts
        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login/verify-otp', ['otp_code' => '999999']);
        }

        // Even with correct code, verification should fail due to attempt limit
        $response = $this->post('/admin/login/verify-otp', [
            'otp_code' => $otp->otp_code,
        ]);

        $response->assertSessionHasErrors('otp_code');
        $this->assertGuest();
    }

    public function test_resend_otp_issues_fresh_code_and_invalidates_previous_code(): void
    {
        Mail::fake();

        $admin = $this->userWithRole('admin');

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $firstOtp = OtpVerification::where('email', $admin->email)
            ->where('purpose', 'admin_login')
            ->firstOrFail();

        $response = $this->post('/admin/login/resend-otp');
        $response->assertSessionHas('status');

        Mail::assertSent(AdminLoginOtpMail::class, 2);

        // Submitting previous code should now fail
        $response = $this->post('/admin/login/verify-otp', [
            'otp_code' => $firstOtp->otp_code,
        ]);

        $response->assertSessionHasErrors('otp_code');
        $this->assertGuest();

        // Submitting newly issued code succeeds
        $latestOtp = OtpVerification::where('email', $admin->email)
            ->where('purpose', 'admin_login')
            ->latest('id')
            ->firstOrFail();

        $this->post('/admin/login/verify-otp', [
            'otp_code' => $latestOtp->otp_code,
        ])->assertRedirect(route('admin.index'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_mail_transport_failure_does_not_authenticate_admin_and_shows_error(): void
    {
        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new \RuntimeException('SMTP delivery failure'));

        $admin = $this->userWithRole('admin');

        $response = $this->from('/login')->post('/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertNull(session('admin_2fa'));
    }

    public function test_resend_mail_transport_failure_shows_error_gracefully(): void
    {
        Mail::fake();

        $admin = $this->userWithRole('admin');

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new \RuntimeException('Connection drop on resend'));

        $response = $this->post('/admin/login/resend-otp');

        $response->assertSessionHasErrors('otp_code');
        $response->assertSessionMissing('status');
    }

    public function test_cancel_2fa_clears_session_and_returns_to_login(): void
    {
        Mail::fake();

        $admin = $this->userWithRole('admin');

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $this->assertNotNull(session('admin_2fa'));

        $response = $this->post('/admin/login/cancel-otp');

        $response->assertRedirect(route('login'));
        $this->assertNull(session('admin_2fa'));
        $this->assertGuest();
    }

    public function test_alumni_and_batch_rep_log_in_directly_without_2fa(): void
    {
        Mail::fake();

        $alumni = $this->userWithRole('alumni');

        $response = $this->post('/login', [
            'email' => $alumni->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($alumni);
        $this->assertNull(session('admin_2fa'));
        Mail::assertNothingSent();

        // Logout and test batch representative
        $this->post('/logout');

        $batchRep = $this->userWithRole('batch-representative');

        $response = $this->post('/login', [
            'email' => $batchRep->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($batchRep);
        $this->assertNull(session('admin_2fa'));
        Mail::assertNothingSent();
    }

    public function test_logout_clears_admin_2fa_session(): void
    {
        Mail::fake();

        $admin = $this->userWithRole('admin');

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $otp = OtpVerification::where('email', $admin->email)
            ->where('purpose', 'admin_login')
            ->firstOrFail();

        $this->post('/admin/login/verify-otp', [
            'otp_code' => $otp->otp_code,
        ]);

        $this->assertAuthenticatedAs($admin);

        // Put a dummy session variable to verify logout clears everything
        session(['admin_2fa' => ['user_id' => $admin->id]]);

        $response = $this->post('/logout');

        $response->assertRedirect(route('home'));
        $this->assertNull(session('admin_2fa'));
        $this->assertGuest();
    }
}
