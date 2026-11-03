<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use App\Models\Batch;
use App\Models\VerificationRequest;

/**
 * Placeholder admin-area controller. Route-level access is not yet
 * restricted by role (see PROJECT_MASTER_SPECIFICATION.md — the
 * authorization system is a later phase), so this must not be linked
 * from public navigation yet.
 */
class AdminDashboardController extends Controller
{
    public function index()
    {
        return view('admin.index', [
            'batchCount' => Batch::count(),
            'alumniCount' => Alumni::count(),
            'pendingVerificationCount' => VerificationRequest::where('status', 'pending')->count(),
        ]);
    }
}
