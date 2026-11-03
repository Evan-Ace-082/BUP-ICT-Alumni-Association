<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\ElectionCandidate;
use App\Models\ElectionPosition;
use App\Services\VotingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ElectionVotingController extends Controller
{
    public function __construct(
        protected VotingService $votingService
    ) {}

    public function index()
    {
        $user = Auth::user();

        // Ongoing online elections
        $ongoingElections = Election::where('type', Election::TYPE_ONLINE)
            ->where('status', Election::STATUS_ONGOING)
            ->with(['positions.candidates'])
            ->latest('start_time')
            ->get();

        // Upcoming online elections
        $upcomingElections = Election::where('type', Election::TYPE_ONLINE)
            ->where(function ($q) {
                $q->where('status', Election::STATUS_DRAFT)
                    ->orWhere(function ($sq) {
                        $sq->where('status', Election::STATUS_ONGOING)
                            ->where('start_time', '>', now());
                    });
            })
            ->with(['positions'])
            ->latest('start_time')
            ->get();

        // Past or finalized online elections
        $pastElections = Election::where('type', Election::TYPE_ONLINE)
            ->whereIn('status', [Election::STATUS_CLOSED, Election::STATUS_FINALIZED])
            ->latest('end_time')
            ->paginate(10);

        return view('dashboard.elections.index', compact('ongoingElections', 'upcomingElections', 'pastElections', 'user'));
    }

    public function show(Election $election)
    {
        // Only online elections are displayed for voting
        if (! $election->isOnline()) {
            abort(404, 'Online voting is only available for online elections.');
        }

        $user = Auth::user();

        $election->load([
            'positions' => fn ($q) => $q->orderBy('display_order')->with([
                'candidates' => fn ($cq) => $cq->with('alumni.batch'),
            ]),
        ]);

        $votedPositionIds = $this->votingService->getVoterCastPositionIds($election, $user);
        $isEligible = (bool) ($user && $user->status === \App\Models\User::STATUS_ACTIVE && $user->alumni && $user->alumni->canLogIn());

        return view('dashboard.elections.show', compact('election', 'votedPositionIds', 'user', 'isEligible'));
    }

    public function vote(Request $request, Election $election)
    {
        $validated = $request->validate([
            'position_id' => ['required', 'exists:election_positions,id'],
            'candidate_id' => ['required', 'exists:election_candidates,id'],
        ]);

        $position = ElectionPosition::findOrFail($validated['position_id']);
        $candidate = ElectionCandidate::findOrFail($validated['candidate_id']);
        $user = Auth::user();

        try {
            $this->votingService->castVote($election, $position, $candidate, $user);
            return back()->with('success', "Your vote for {$position->name} has been securely cast.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?? 'Unable to cast vote.';
            return back()->withErrors($e->errors())->with('error', $msg);
        }
    }
}
