<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompleteRegistrationRequest;
use App\Http\Requests\Auth\LookupStudentIdRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\Alumni;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RegistrationController extends Controller
{
    public function __construct(protected OtpService $otp) {}

    public function create()
    {
        return view('auth.register');
    }

    /**
     * Step 1: find the official alumni record by Student ID. Batch is
     * never derived from the ID — it's read straight off the matched
     * record.
     */
    public function lookup(LookupStudentIdRequest $request)
    {
        $studentId = $request->validated()['student_id'];

        $alumni = Alumni::where('student_id', $studentId)->first();

        if (! $alumni) {
            return redirect()->route('verification.create', ['student_id' => $studentId]);
        }

        if ($alumni->status === Alumni::STATUS_INACTIVE) {
            return back()->withErrors([
                'student_id' => 'This alumni record is inactive. Please contact the department.',
            ])->withInput();
        }

        if ($alumni->user_id) {
            return back()->withErrors([
                'student_id' => 'This alumni record is already linked to an account. Please log in instead.',
            ])->withInput();
        }

        session(['registration.alumni_id' => $alumni->id]);

        return redirect()->route('register.details');
    }

    /**
     * Step 2: show the matched name/batch and collect NEW account
     * credentials. The submitted email/phone do not need to match
     * anything on the master record.
     */
    public function showDetails()
    {
        return view('auth.register-details', ['alumni' => $this->resolveSessionAlumni()]);
    }

    public function complete(CompleteRegistrationRequest $request)
    {
        $alumni = $this->resolveSessionAlumni();
        $data = $request->validated();

        try {
            $this->otp->issue($data['email'], 'registration');
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors([
                'email' => 'Unable to send verification email. Please check your address or try again later.',
            ]);
        }

        // Held only in session until the OTP is verified — no partial
        // `users` row is created for an abandoned registration.
        session(['registration.draft' => [
            'alumni_id' => $alumni->id,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
        ]]);

        return redirect()->route('register.otp')->with('status', 'A verification code has been sent to your email.');
    }

    /**
     * Step 3: OTP sent to the newly submitted account email — never to
     * whatever email is on the master record.
     */
    public function showOtp()
    {
        return view('auth.verify-otp', ['email' => $this->resolveSessionDraft()['email']]);
    }

    public function verifyOtp(VerifyOtpRequest $request)
    {
        $draft = $this->resolveSessionDraft();

        if (! $this->otp->verify($draft['email'], $request->validated()['otp_code'], 'registration')) {
            return back()->withErrors(['otp_code' => 'That code is invalid or has expired.']);
        }

        $result = DB::transaction(function () use ($draft) {
            // Re-fetch under a row lock and re-check user_id is still
            // null, closing the window where two concurrent
            // registration attempts against the same unclaimed alumni
            // record could otherwise both pass the earlier lookup()
            // check and both try to complete the claim.
            $alumni = Alumni::whereKey($draft['alumni_id'])->lockForUpdate()->firstOrFail();

            if ($alumni->user_id) {
                return null;
            }

            $user = User::create([
                'name' => $alumni->full_name,
                'email' => $draft['email'],
                'phone' => $draft['phone'],
                'password' => $draft['password'],
            ]);

            $alumniRole = \App\Models\Role::where('slug', 'alumni')->first();
            if ($alumniRole && ! $user->hasRole('alumni')) {
                $user->roles()->syncWithoutDetaching([$alumniRole->id]);
            }

            $alumni->update([
                'user_id' => $user->id,
                'status' => Alumni::STATUS_ACTIVE,
                'verification_status' => Alumni::VERIFICATION_STATUS_PENDING,
            ]);

            return $user;
        });

        session()->forget(['registration.alumni_id', 'registration.draft']);

        if (! $result) {
            return redirect()->route('login')->withErrors([
                'email' => 'This alumni record was just claimed by someone else. Please contact the department.',
            ]);
        }

        Auth::login($result);

        return redirect()->route('dashboard');
    }

    public function resendOtp()
    {
        $draft = $this->resolveSessionDraft();

        try {
            $this->otp->issue($draft['email'], 'registration');
        } catch (\Throwable $e) {
            return back()->withErrors([
                'otp_code' => 'Unable to send a new verification code. Please try again later.',
            ]);
        }

        return back()->with('status', 'A new verification code has been sent.');
    }

    protected function resolveSessionAlumni(): Alumni
    {
        $alumniId = session('registration.alumni_id');

        abort_unless($alumniId, 419, 'Your registration session has expired. Please start again.');

        return Alumni::findOrFail($alumniId);
    }

    protected function resolveSessionDraft(): array
    {
        $draft = session('registration.draft');

        abort_unless($draft, 419, 'Your registration session has expired. Please start again.');

        return $draft;
    }
}
