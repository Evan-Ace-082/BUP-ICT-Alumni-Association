<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use App\Models\Batch;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminStudentController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', Student::VERIFICATION_STATUS_PENDING);

        $query = Student::with(['user', 'batch', 'verifier'])
            ->latest();

        if ($status !== 'all') {
            $query->where('verification_status', $status);
        }

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', Alumni::likeOperator(), "%{$search}%")
                    ->orWhere('student_id', Alumni::likeOperator(), "%{$search}%")
                    ->orWhere('email', Alumni::likeOperator(), "%{$search}%");
            });
        }

        $students = $query->paginate(15)->withQueryString();
        $batches = Batch::active()->orderBy('batch_code')->get();

        return view('admin.students.index', [
            'students' => $students,
            'currentStatus' => $status,
            'batches' => $batches,
            'search' => $search,
        ]);
    }

    public function show(Student $student)
    {
        $student->load(['user', 'batch', 'verifier']);
        $batches = Batch::active()->orderBy('batch_code')->get();

        return view('admin.students.show', [
            'student' => $student,
            'batches' => $batches,
        ]);
    }

    public function verify(Request $request, Student $student)
    {
        $student->update([
            'verification_status' => Student::VERIFICATION_STATUS_VERIFIED,
            'verified_at' => now(),
            'verified_by' => Auth::id(),
            'admin_notes' => $request->input('admin_notes', $student->admin_notes),
            'rejection_reason' => null,
        ]);

        return back()->with('status', "Student account for {$student->full_name} ({$student->student_id}) has been verified.");
    }

    public function reject(Request $request, Student $student)
    {
        $request->validate(['rejection_reason' => ['required', 'string', 'max:1000']]);

        $student->update([
            'verification_status' => Student::VERIFICATION_STATUS_REJECTED,
            'rejection_reason' => $request->input('rejection_reason'),
            'admin_notes' => $request->input('admin_notes', $student->admin_notes),
        ]);

        return back()->with('status', "Student verification for {$student->full_name} has been rejected.");
    }

    /**
     * Controlled transition from verified student to official alumnus (Section 11)
     */
    public function transitionToAlumnus(Request $request, Student $student)
    {
        $request->validate([
            'graduation_year' => ['required', 'integer', 'min:2015', 'max:' . now()->year],
            'batch_id' => ['required', 'exists:batches,id'],
        ]);

        abort_unless($student->isVerified(), 422, 'Only verified students can be transitioned to alumni.');

        DB::transaction(function () use ($request, $student) {
            $user = $student->user;

            // Check if an alumni record already exists for this student_id
            $alumni = Alumni::where('student_id', $student->student_id)->first();

            if ($alumni) {
                $alumni->update([
                    'user_id' => $user?->id,
                    'status' => Alumni::STATUS_ACTIVE,
                    'verification_status' => Alumni::VERIFICATION_STATUS_VERIFIED,
                    'graduation_year' => $request->input('graduation_year'),
                    'batch_id' => $request->input('batch_id'),
                ]);
            } else {
                Alumni::create([
                    'student_id' => $student->student_id,
                    'full_name' => $student->full_name,
                    'email' => $student->email,
                    'phone' => $student->phone,
                    'batch_id' => $request->input('batch_id'),
                    'session' => $student->session,
                    'graduation_year' => $request->input('graduation_year'),
                    'user_id' => $user?->id,
                    'status' => Alumni::STATUS_ACTIVE,
                    'verification_status' => Alumni::VERIFICATION_STATUS_VERIFIED,
                    'verified_at' => now(),
                    'verified_by' => Auth::id(),
                ]);
            }

            // Assign alumni role to user
            if ($user) {
                $alumniRole = Role::firstOrCreate(['slug' => 'alumni'], ['name' => 'Alumni']);
                $user->roles()->syncWithoutDetaching([$alumniRole->id]);
            }

            // Mark student record as graduated
            $student->update([
                'status' => Student::STATUS_GRADUATED,
                'admin_notes' => ($student->admin_notes ? $student->admin_notes . "\n" : '') . "Transitioned to official alumni status on " . now()->toFormattedDateString() . " by " . Auth::user()->name,
            ]);
        });

        return redirect()->route('admin.students.index')
            ->with('status', "Student {$student->full_name} has successfully transitioned to official verified alumni status.");
    }
}
