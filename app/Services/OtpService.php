<?php

namespace App\Services;

use App\Mail\AdminLoginOtpMail;
use App\Mail\AlumniOtpMail;
use App\Mail\PasswordResetOtpMail;
use App\Models\OtpVerification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    protected int $expiryMinutes = 10;

    protected int $passwordResetExpiryMinutes = 5;

    protected int $maxAttempts = 5;

    /**
     * Issue a fresh OTP to the given email and send it. For password_reset,
     * the code is securely hashed (never stored plaintext) and expires in 5 minutes.
     * Older unverified OTPs for the email and purpose are invalidated.
     */
    public function issue(string $email, string $purpose = 'registration'): OtpVerification
    {
        $code = (string) random_int(100000, 999999);
        $expiryMinutes = ($purpose === 'password_reset') ? $this->passwordResetExpiryMinutes : $this->expiryMinutes;

        // Invalidate any older unverified OTPs for this email and purpose to prevent confusion/replay
        OtpVerification::where('email', $email)
            ->where('purpose', $purpose)
            ->where('is_verified', false)
            ->delete();

        // Store secure hash for password reset, or plaintext for backward-compatible flows
        $storedCode = ($purpose === 'password_reset') ? Hash::make($code) : $code;

        $otp = OtpVerification::create([
            'email' => $email,
            'otp_code' => $storedCode,
            'purpose' => $purpose,
            'expires_at' => Carbon::now()->addMinutes($expiryMinutes),
            'is_verified' => false,
            'attempts' => 0,
        ]);

        try {
            if ($purpose === 'admin_login') {
                Mail::to($email)->send(new AdminLoginOtpMail($code, $expiryMinutes));
            } elseif ($purpose === 'password_reset') {
                Mail::to($email)->send(new PasswordResetOtpMail($code, $expiryMinutes));
            } else {
                Mail::to($email)->send(new AlumniOtpMail($code, $expiryMinutes));
            }
        } catch (\Throwable $e) {
            // Clean up the unsent OTP record so it does not linger or block attempts
            $otp->delete();

            // Secure logging: log failure details without leaking the OTP code or credentials
            Log::error('Failed to send OTP verification email', [
                'recipient' => $email,
                'purpose' => $purpose,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $otp;
    }

    /**
     * Verify a submitted code against the most recent unverified OTP
     * for this email/purpose. Returns false on any mismatch, expiry,
     * missing record, or exceeded attempt count.
     */
    public function verify(string $email, string $code, string $purpose = 'registration'): bool
    {
        $otp = OtpVerification::where('email', $email)
            ->where('purpose', $purpose)
            ->where('is_verified', false)
            ->latest('id')
            ->first();

        if (! $otp || $otp->attempts >= $this->maxAttempts) {
            return false;
        }

        if ($otp->isExpired()) {
            $otp->increment('attempts');

            return false;
        }

        $isHashed = ($purpose === 'password_reset') || str_starts_with($otp->otp_code, '$2y$') || strlen($otp->otp_code) > 20;
        $matches = $isHashed ? Hash::check($code, $otp->otp_code) : hash_equals($otp->otp_code, $code);

        if (! $matches) {
            $otp->increment('attempts');

            return false;
        }

        $otp->update(['is_verified' => true]);

        return true;
    }

    /**
     * Invalidate all OTPs for a given email and purpose (e.g. after password reset).
     */
    public function invalidateAll(string $email, string $purpose): void
    {
        OtpVerification::where('email', $email)
            ->where('purpose', $purpose)
            ->delete();
    }
}
