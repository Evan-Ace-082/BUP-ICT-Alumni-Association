<?php

namespace App\Http\Controllers;

use App\Models\Election;

class ElectionResultController extends Controller
{
    public function index()
    {
        $finalizedElections = Election::where('status', Election::STATUS_FINALIZED)
            ->with(['positions.candidates.alumni.batch'])
            ->latest('finalized_at')
            ->paginate(12);

        return view('elections.results.index', compact('finalizedElections'));
    }

    public function show(Election $election)
    {
        if (! $election->isFinalized()) {
            abort(404, 'Election results are not yet finalized or publicly available.');
        }

        $election->load([
            'positions' => fn ($q) => $q->orderBy('display_order')->with([
                'tieResolvedBy',
                'candidates' => fn ($cq) => $cq->with('alumni.batch')->withCount('votes'),
            ]),
        ]);

        return view('elections.results.show', compact('election'));
    }
}
