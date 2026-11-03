<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use Illuminate\Http\Request;

/**
 * Authenticated dashboard area. Reuses the existing User->alumni
 * (hasOne) and Alumni->batch (belongsTo) relationships — no new
 * relationship or table involved.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $alumni = $user?->alumni;

        if ($alumni) {
            $alumni->load('batch');
        }

        return view('dashboard.index', compact('user', 'alumni'));
    }

    public function batch(Request $request)
    {
        $user = auth()->user();
        $alumniProfile = $user->alumni;
        $batch = $alumniProfile?->batch ?? $user->assignedBatch;

        $batchmates = null;
        if ($batch) {
            $query = Alumni::query()
                ->where('batch_id', $batch->id)
                ->whereNotIn('status', [Alumni::STATUS_INACTIVE, Alumni::STATUS_REJECTED])
                ->with('batch');

            if ($search = trim((string) $request->input('search'))) {
                $like = Alumni::likeOperator($query->getConnection()->getName());
                $query->where(function ($q) use ($search, $like) {
                    $q->where('full_name', $like, "%{$search}%")
                        ->orWhere('student_id', $like, "%{$search}%")
                        ->orWhere('company', $like, "%{$search}%")
                        ->orWhere('current_institution', $like, "%{$search}%")
                        ->orWhere('designation', $like, "%{$search}%");
                });
            }

            $batchmates = $query->orderBy('full_name')->paginate(12)->withQueryString();
        }

        return view('dashboard.batch', [
            'alumni' => $alumniProfile,
            'batch' => $batch,
            'batchmates' => $batchmates,
            'search' => $request->input('search', ''),
        ]);
    }
}
