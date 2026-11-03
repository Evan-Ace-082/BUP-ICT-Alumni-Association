<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\Role;
use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VerificationController extends Controller
{
    public function index(Request $request)
    {
        $query = VerificationRequest::with(['batch', 'reviewer']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $like = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                $q->where('full_name', $like, "%{$search}%")
                    ->orWhere('student_id', $like, "%{$search}%")
                    ->orWhere('email', $like, "%{$search}%");
            });
        }

        $requests = $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $pendingCount = VerificationRequest::where('status', 'pending')->count();
        $filters = $request->only(['status', 'search']);

        return view('admin.verifications.index', compact('requests', 'pendingCount', 'filters'));
    }

    public function show(VerificationRequest $verificationRequest)
    {
        $verificationRequest->load(['batch', 'reviewer']);

        $existingAlumni = null;
        if ($verificationRequest->student_id) {
            $existingAlumni = Alumni::where('student_id', $verificationRequest->student_id)->first();
        }
        if (! $existingAlumni && $verificationRequest->email) {
            $existingAlumni = Alumni::where('email', $verificationRequest->email)->first();
        }

        $existingUser = User::where('email', $verificationRequest->email)->first();
        $batches = Batch::orderBy('batch_name')->get();

        return view('admin.verifications.show', compact('verificationRequest', 'existingAlumni', 'existingUser', 'batches'));
    }

    public function approve(Request $request, VerificationRequest $verificationRequest)
    {
        if ($verificationRequest->status !== 'pending') {
            return back()->withErrors(['error' => 'This verification request has already been reviewed.']);
        }

        if ($verificationRequest->email === Auth::user()->email) {
            return back()->withErrors(['error' => 'You cannot approve your own verification request.']);
        }

        $validated = $request->validate([
            'batch_id' => ['required', 'exists:batches,id'],
            'student_id' => ['required', 'string', 'max:50'],
        ]);

        DB::transaction(function () use ($verificationRequest, $validated) {
            $batchId = $validated['batch_id'];
            $studentId = $validated['student_id'];

            // Find or create official Alumni record without duplicates
            $alumni = Alumni::where('student_id', $studentId)->first();
            if (! $alumni && $verificationRequest->email) {
                $alumni = Alumni::where('email', $verificationRequest->email)->first();
            }

            // Link existing User account if user already registered with this email
            $user = User::where('email', $verificationRequest->email)->first();
            $userId = $alumni?->user_id ?? $user?->id;
            $status = $userId ? Alumni::STATUS_ACTIVE : Alumni::STATUS_UNCLAIMED;

            if ($alumni) {
                $alumni->update([
                    'student_id' => $studentId,
                    'batch_id' => $batchId,
                    'full_name' => $alumni->full_name ?: $verificationRequest->full_name,
                    'phone' => $alumni->phone ?: $verificationRequest->phone,
                    'user_id' => $userId,
                    'status' => $status,
                ]);
            } else {
                $alumni = Alumni::create([
                    'student_id' => $studentId,
                    'full_name' => $verificationRequest->full_name,
                    'email' => $verificationRequest->email,
                    'phone' => $verificationRequest->phone,
                    'batch_id' => $batchId,
                    'user_id' => $userId,
                    'status' => $status,
                ]);
            }

            if ($user) {
                if ($user->status !== User::STATUS_ACTIVE) {
                    $user->update(['status' => User::STATUS_ACTIVE]);
                }
                $alumniRole = Role::where('slug', 'alumni')->first();
                if ($alumniRole && ! $user->hasRole('alumni')) {
                    $user->roles()->syncWithoutDetaching([$alumniRole->id]);
                }
            }

            $verificationRequest->update([
                'status' => 'approved',
                'batch_id' => $batchId,
                'student_id' => $studentId,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            AuditLog::create([
                'actor_id' => Auth::id(),
                'target_type' => VerificationRequest::class,
                'target_id' => $verificationRequest->id,
                'action' => 'verification_approved',
                'details' => "Verification request for {$verificationRequest->full_name} ({$studentId}) approved.",
            ]);
        });

        return redirect()->route('admin.verifications.index')
            ->with('success', "Verification request for {$verificationRequest->full_name} has been approved.");
    }

    public function reject(Request $request, VerificationRequest $verificationRequest)
    {
        if ($verificationRequest->status !== 'pending') {
            return back()->withErrors(['error' => 'This verification request has already been reviewed.']);
        }

        if ($verificationRequest->email === Auth::user()->email) {
            return back()->withErrors(['error' => 'You cannot reject your own verification request.']);
        }

        $verificationRequest->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        AuditLog::create([
            'actor_id' => Auth::id(),
            'target_type' => VerificationRequest::class,
            'target_id' => $verificationRequest->id,
            'action' => 'verification_rejected',
            'details' => "Verification request for {$verificationRequest->full_name} rejected.",
        ]);

        return redirect()->route('admin.verifications.index')
            ->with('success', "Verification request for {$verificationRequest->full_name} has been rejected.");
    }
}
