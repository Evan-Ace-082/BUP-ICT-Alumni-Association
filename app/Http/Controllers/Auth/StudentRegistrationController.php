<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StudentRegistrationController extends Controller
{
    public function create()
    {
        $batches = Batch::active()->orderBy('batch_code')->get();

        return view('auth.register-student', [
            'batches' => $batches,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => [
                'required',
                'string',
                'max:50',
                Rule::unique('students', 'student_id'),
                Rule::unique('alumni', 'student_id'), // Prevent duplicate alumni identities
            ],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
                Rule::unique('students', 'email'),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'session' => ['required', 'string', 'max:50'],
            'batch_id' => ['nullable', 'exists:batches,id'],
            'enrollment_year' => ['required', 'integer', 'min:2015', 'max:' . (now()->year + 1)],
            'expected_graduation_year' => ['required', 'integer', 'min:' . now()->year, 'max:' . (now()->year + 10)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'student_id.unique' => 'This Student ID is already registered or belongs to an alumni record.',
            'email.unique' => 'This email address is already linked to an account.',
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
            ]);

            $studentRole = Role::firstOrCreate(['slug' => 'student'], [
                'name' => 'Student',
                'description' => 'Currently enrolled BUP ICT student.',
            ]);

            $user->roles()->syncWithoutDetaching([$studentRole->id]);

            Student::create([
                'user_id' => $user->id,
                'student_id' => $validated['student_id'],
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'batch_id' => $validated['batch_id'] ?? null,
                'department' => 'Information and Communication Technology',
                'session' => $validated['session'],
                'enrollment_year' => $validated['enrollment_year'],
                'expected_graduation_year' => $validated['expected_graduation_year'],
                'status' => Student::STATUS_ACTIVE,
                'verification_status' => Student::VERIFICATION_STATUS_PENDING, // requires admin verification
            ]);

            return $user;
        });

        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('status', 'Your student account has been registered and is pending department administrative verification. Once verified, you will have full access to Find a Mentor.');
    }
}
