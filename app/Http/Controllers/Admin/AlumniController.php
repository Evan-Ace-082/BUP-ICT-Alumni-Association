<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use App\Models\Batch;
use Illuminate\Http\Request;

/**
 * Read-only alumni directory for admins. This controller never
 * creates, updates, or deletes an Alumni record — it only queries the
 * existing table. Populating real records is the Excel import's job
 * (see AlumniImportController / Services\Import), which is
 * deliberately not run against real data yet.
 */
class AlumniController extends Controller
{
    public function index(Request $request)
    {
        $query = Alumni::with('batch');

        if ($search = trim((string) $request->input('search'))) {
            $like = Alumni::likeOperator($query->getConnection()->getName());
            $query->where(function ($q) use ($search, $like) {
                $q->where('student_id', $like, "%{$search}%")
                    ->orWhere('full_name', $like, "%{$search}%")
                    ->orWhere('email', $like, "%{$search}%");
            });
        }

        if ($batchId = $request->input('batch_id')) {
            $query->where('batch_id', $batchId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $alumni = $query->orderBy('full_name')->paginate(20)->withQueryString();

        $batches = Batch::orderBy('batch_name')->get();

        return view('admin.alumni.index', [
            'alumni' => $alumni,
            'batches' => $batches,
            'filters' => $request->only(['search', 'batch_id', 'status']),
        ]);
    }

    public function show(Alumni $alumni)
    {
        $alumni->load('batch', 'user');

        return view('admin.alumni.show', compact('alumni'));
    }
}
