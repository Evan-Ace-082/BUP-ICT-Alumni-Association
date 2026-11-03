<?php

namespace App\Http\Controllers;

use App\Models\Committee;

class CommitteeController extends Controller
{
    public function index()
    {
        $currentCommittee = Committee::where('is_current', true)
            ->with(['members.alumni.batch', 'election'])
            ->first();

        return view('committees.index', compact('currentCommittee'));
    }

    public function history()
    {
        $committees = Committee::where('is_current', false)
            ->with(['members.alumni.batch', 'election'])
            ->latest('term')
            ->paginate(10);

        return view('committees.history', compact('committees'));
    }

    public function show(Committee $committee)
    {
        $committee->load(['members.alumni.batch', 'election']);

        return view('committees.show', compact('committee'));
    }
}
