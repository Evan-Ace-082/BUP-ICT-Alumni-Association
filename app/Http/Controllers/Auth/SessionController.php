<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SessionController extends Controller
{
    public function __construct(protected OtpService $otp) {}

    public function create()
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request)
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors([
                'email' => 'Those credentials do not match our records.',
            ])->onlyInput('email');
        }

        if ($user->isSuspended()) {
            return back()->withErrors([
                'email' => 'Your account has been suspended. Please contact the administrator.',
            ])->onlyInput('email');
        }

        $isAdmin = $user->hasRole('admin') || $user->hasRole('super-admin');

        // Non-administrative users (Alumni, Batch Representatives) log in directly
        if (! $isAdmin) {
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        // Administrator and Super Administrator: Enforce mandatory Email 2FA
        // Do NOT log in yet; keep user unauthenticated.
        session(['admin_2fa' => [
            'user_id' => $user->id,
            'remember' => $request->boolean('remember'),
        ]]);

        try {
            $this->otp->issue($user->email, 'admin_login');
        } catch (\Throwable $e) {
            session()->forget('admin_2fa');

            return back()->withInput()->withErrors([
                'email' => 'Unable to send 2FA verification email right now. Please try again later.',
            ]);
        }

        return redirect()->route('admin.login.otp')->with('status', 'A 6-digit verification code has been sent to your registered email.');
    }

    public function showAdminOtp()
    {
        $sessionData = session('admin_2fa');
        if (! $sessionData || ! isset($sessionData['user_id'])) {
            return redirect()->route('login');
        }

        $user = User::find($sessionData['user_id']);
        if (! $user || $user->isSuspended() || (! $user->hasRole('admin') && ! $user->hasRole('super-admin'))) {
            session()->forget('admin_2fa');

            return redirect()->route('login')->withErrors([
                'email' => 'Unauthorized or invalid administrator session.',
            ]);
        }

        return view('auth.admin-verify-otp', ['email' => $user->email]);
    }

    public function verifyAdminOtp(Request $request)
    {
        $request->validate([
            'otp_code' => ['required', 'string', 'size:6'],
        ]);

        $sessionData = session('admin_2fa');
        if (! $sessionData || ! isset($sessionData['user_id'])) {
            return redirect()->route('login')->withErrors([
                'email' => 'Your 2FA session has expired. Please sign in again.',
            ]);
        }

        $user = User::find($sessionData['user_id']);
        if (! $user || $user->isSuspended() || (! $user->hasRole('admin') && ! $user->hasRole('super-admin'))) {
            session()->forget('admin_2fa');

            return redirect()->route('login')->withErrors([
                'email' => 'Unauthorized or suspended administrator account.',
            ]);
        }

        if (! $this->otp->verify($user->email, $request->input('otp_code'), 'admin_login')) {
            return back()->withErrors([
                'otp_code' => 'That code is invalid or has expired.',
            ]);
        }

        session()->forget('admin_2fa');
        $request->session()->regenerate();
        Auth::login($user, (bool) ($sessionData['remember'] ?? false));

        return redirect()->intended(route('admin.index'));
    }

    public function resendAdminOtp()
    {
        $sessionData = session('admin_2fa');
        if (! $sessionData || ! isset($sessionData['user_id'])) {
            return redirect()->route('login');
        }

        $user = User::find($sessionData['user_id']);
        if (! $user || $user->isSuspended() || (! $user->hasRole('admin') && ! $user->hasRole('super-admin'))) {
            session()->forget('admin_2fa');

            return redirect()->route('login');
        }

        try {
            $this->otp->issue($user->email, 'admin_login');
        } catch (\Throwable $e) {
            return back()->withErrors([
                'otp_code' => 'Unable to send a new verification code. Please try again later.',
            ]);
        }

        return back()->with('status', 'A new 2FA verification code has been sent to your email.');
    }

    public function cancelAdminOtp(Request $request)
    {
        $request->session()->forget('admin_2fa');

        return redirect()->route('login');
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->forget('admin_2fa');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
