<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use App\Services\AlumniVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AlumniVerificationController extends Controller
{
    public function __construct(protected AlumniVerificationService $verificationService) {}

    public function index(Request $request)
    {
        $query = Alumni::query()
            ->whereNotNull('user_id')
            ->with(['batch', 'user', 'verifier', 'rejecter']);

        $status = $request->input('status', 'pending');
        if ($status && in_array($status, [Alumni::VERIFICATION_STATUS_PENDING, Alumni::VERIFICATION_STATUS_VERIFIED, Alumni::VERIFICATION_STATUS_REJECTED], true)) {
            $query->where('verification_status', $status);
        }

        if ($search = trim((string) $request->input('search'))) {
            $like = Alumni::likeOperator($query->getConnection()->getName());
            $query->where(function ($q) use ($search, $like) {
                $q->where('full_name', $like, "%{$search}%")
                    ->orWhere('student_id', $like, "%{$search}%")
                    ->orWhere('email', $like, "%{$search}%")
                    ->orWhere('alumni_id', $like, "%{$search}%");
            });
        }

        $requests = $query->orderByRaw("CASE WHEN verification_status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'pending' => Alumni::whereNotNull('user_id')->where('verification_status', Alumni::VERIFICATION_STATUS_PENDING)->count(),
            'verified' => Alumni::whereNotNull('user_id')->where('verification_status', Alumni::VERIFICATION_STATUS_VERIFIED)->count(),
            'rejected' => Alumni::whereNotNull('user_id')->where('verification_status', Alumni::VERIFICATION_STATUS_REJECTED)->count(),
        ];

        return view('admin.alumni-verifications.index', [
            'requests' => $requests,
            'counts' => $counts,
            'filters' => $request->only(['status', 'search']),
            'currentStatus' => $status,
        ]);
    }

    public function show(Alumni $alumni)
    {
        abort_unless($alumni->user_id, 404, 'No registered user account linked to this alumni record.');

        $alumni->load(['batch', 'user', 'verifier', 'rejecter']);

        return view('admin.alumni-verifications.show', compact('alumni'));
    }

    public function approve(Request $request, Alumni $alumni)
    {
        try {
            $this->verificationService->approve($alumni, Auth::user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('admin.alumni-verifications.index')
            ->with('success', "Alumni {$alumni->full_name} ({$alumni->student_id}) has been approved and issued Alumni ID {$alumni->fresh()->alumni_id}.");
    }

    public function reject(Request $request, Alumni $alumni)
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->verificationService->reject($alumni, Auth::user(), $validated['reason'] ?? null);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('admin.alumni-verifications.index')
            ->with('success', "Alumni verification request for {$alumni->full_name} has been rejected.");
    }
}
