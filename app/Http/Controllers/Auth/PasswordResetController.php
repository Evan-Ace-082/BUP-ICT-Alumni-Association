<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function __construct(protected OtpService $otp) {}

    /**
     * Stage 1: Display the "Forgot Password" form to enter registered email.
     */
    public function create()
    {
        return view('auth.forgot-password');
    }

    /**
     * Stage 1 submit: Validate email, dispatch 6-digit OTP if user exists,
     * and redirect to OTP verification page with a generic response.
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = $request->input('email');
        $user = User::where('email', $email)->first();

        // Only registered accounts receive an OTP email. Unregistered accounts
        // do not trigger mail, but still receive an identical generic response
        // so attackers cannot enumerate valid user emails.
        if ($user) {
            try {
                $this->otp->issue($email, 'password_reset');
            } catch (\Throwable $e) {
                return back()->withInput()->withErrors([
                    'email' => 'Unable to send verification email right now. Please try again later.',
                ]);
            }
        }

        // Store reset state in session
        session([
            'password_reset' => [
                'email' => $email,
                'last_sent_at' => now()->timestamp,
            ],
        ]);

        return redirect()->route('password.otp')->with(
            'status',
            'If an account exists with that email address, a 6-digit verification code has been sent.'
        );
    }

    /**
     * Stage 2: Display the OTP verification form.
     */
    public function showOtp()
    {
        $sessionData = session('password_reset');
        if (! $sessionData || ! isset($sessionData['email'])) {
            return redirect()->route('password.request')->withErrors([
                'email' => 'Please enter your email to request a verification code.',
            ]);
        }

        $lastSentAt = $sessionData['last_sent_at'] ?? 0;
        $elapsed = now()->timestamp - $lastSentAt;
        $cooldownRemaining = max(0, 60 - $elapsed);

        return view('auth.verify-password-otp', [
            'email' => $sessionData['email'],
            'cooldownRemaining' => $cooldownRemaining,
        ]);
    }

    /**
     * Stage 2 submit: Verify the 6-digit OTP code.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp_code' => ['required', 'string', 'size:6'],
        ]);

        $sessionData = session('password_reset');
        if (! $sessionData || ! isset($sessionData['email'])) {
            return redirect()->route('password.request')->withErrors([
                'email' => 'Your password reset session has expired. Please try again.',
            ]);
        }

        $email = $sessionData['email'];
        $code = $request->input('otp_code');

        if (! $this->otp->verify($email, $code, 'password_reset')) {
            return back()->withErrors([
                'otp_code' => 'That verification code is invalid, expired, or has exceeded maximum attempts.',
            ]);
        }

        // Generate a cryptographically secure reset authorization token
        $resetToken = Str::random(64);

        session([
            'password_reset_auth' => [
                'email' => $email,
                'token' => $resetToken,
                'authorized_at' => now()->timestamp,
            ],
        ]);

        return redirect()->route('password.reset', ['token' => $resetToken]);
    }

    /**
     * Resend verification code with a 60-second cooldown.
     */
    public function resendOtp(Request $request)
    {
        $sessionData = session('password_reset');
        if (! $sessionData || ! isset($sessionData['email'])) {
            return redirect()->route('password.request')->withErrors([
                'email' => 'Your session has expired. Please request a new code.',
            ]);
        }

        $lastSentAt = $sessionData['last_sent_at'] ?? 0;
        $elapsed = now()->timestamp - $lastSentAt;
        if ($elapsed < 60) {
            $remaining = 60 - $elapsed;

            return back()->withErrors([
                'otp_code' => "Please wait {$remaining} seconds before requesting a new verification code.",
            ]);
        }

        $email = $sessionData['email'];
        $user = User::where('email', $email)->first();

        if ($user) {
            try {
                $this->otp->issue($email, 'password_reset');
            } catch (\Throwable $e) {
                return back()->withErrors([
                    'otp_code' => 'Unable to send a new verification code right now. Please try again later.',
                ]);
            }
        }

        session([
            'password_reset.last_sent_at' => now()->timestamp,
        ]);

        return back()->with('status', 'A new verification code has been sent to your email.');
    }

    /**
     * Stage 3: Display the "Set New Password" form.
     */
    public function showReset(Request $request, string $token)
    {
        $auth = session('password_reset_auth');

        if (! $auth || ! isset($auth['token'], $auth['email']) || ! hash_equals($auth['token'], $token)) {
            return redirect()->route('password.request')->withErrors([
                'email' => 'You must verify your email with a verification code before creating a new password.',
            ]);
        }

        // Enforce 15-minute maximum lifetime for reset authorization
        if (now()->timestamp - $auth['authorized_at'] > 900) {
            session()->forget('password_reset_auth');

            return redirect()->route('password.request')->withErrors([
                'email' => 'Your password reset authorization has expired. Please request a new code.',
            ]);
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $auth['email'],
        ]);
    }

    /**
     * Stage 3 submit: Update password, securely hash with Hash facade,
     * and invalidate the reset authorization and OTP records.
     */
    public function update(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $auth = session('password_reset_auth');

        if (! $auth || ! isset($auth['token'], $auth['email']) || ! hash_equals($auth['token'], $request->token)) {
            return redirect()->route('password.request')->withErrors([
                'email' => 'Your password reset session has expired or is invalid. Please request a new code.',
            ]);
        }

        if (now()->timestamp - $auth['authorized_at'] > 900) {
            session()->forget('password_reset_auth');

            return redirect()->route('password.request')->withErrors([
                'email' => 'Your password reset authorization has expired. Please request a new code.',
            ]);
        }

        // Resolve user strictly from server-side verified authorization
        $user = User::where('email', $auth['email'])->first();

        if (! $user) {
            session()->forget(['password_reset', 'password_reset_auth']);

            return redirect()->route('password.request')->withErrors([
                'email' => 'Unable to find an account for that email address.',
            ]);
        }

        // Securely hash and update the password
        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        // Immediately invalidate OTP records and reset authorization session
        $this->otp->invalidateAll($auth['email'], 'password_reset');
        session()->forget(['password_reset', 'password_reset_auth']);
        $request->session()->regenerate();

        return redirect()->route('login')->with(
            'status',
            'Your password has been reset successfully. You can now log in with your new password.'
        );
    }
}
