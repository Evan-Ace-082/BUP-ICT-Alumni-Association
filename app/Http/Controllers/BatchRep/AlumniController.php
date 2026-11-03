<?php

namespace App\Http\Controllers\BatchRep;

use App\Http\Controllers\Controller;
use App\Http\Requests\BatchRep\UpdateAlumniRequest;
use App\Models\Alumni;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Everything here is scoped to the logged-in representative's own
 * assigned_batch_id — never system-wide, never another batch. This is
 * enforced twice, deliberately: the query itself only ever touches
 * their batch (index/show/update all filter by batch_id), AND
 * Gate::authorize('update', $alumni) re-checks against AlumniPolicy
 * before any write. A representative with no assigned batch can reach
 * these routes (role:batch-representative only checks the role) but
 * sees/can change nothing, since assigned_batch_id is null.
 */
class AlumniController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Alumni::query()->where('batch_id', $user->assigned_batch_id);

        if ($search = trim((string) $request->input('search'))) {
            $query->searchNameOrStudentId($search);
        }

        $alumni = $query->orderBy('full_name')->paginate(20)->withQueryString();

        return view('batch-rep.alumni.index', [
            'alumni' => $alumni,
            'batch' => $user->assignedBatch,
            'filters' => $request->only('search'),
        ]);
    }

    public function edit(Alumni $alumni)
    {
        Gate::authorize('update', $alumni);

        return view('batch-rep.alumni.edit', compact('alumni'));
    }

    public function update(UpdateAlumniRequest $request, Alumni $alumni)
    {
        Gate::authorize('update', $alumni);

        $alumni->update($request->validated());

        return redirect()->route('batch-rep.alumni.index')->with('status', 'Alumni record updated.');
    }
}
