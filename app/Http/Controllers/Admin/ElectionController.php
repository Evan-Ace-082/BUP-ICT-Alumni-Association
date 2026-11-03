<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use App\Models\AuditLog;
use App\Models\Election;
use App\Models\ElectionCandidate;
use App\Models\ElectionPosition;
use App\Services\ElectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ElectionController extends Controller
{
    public function __construct(
        protected ElectionService $electionService
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Election::class);

        $filter = $request->query('filter', 'active');

        $query = Election::with(['creator', 'positions', 'candidates', 'committee'])
            ->withCount(['positions', 'candidates', 'votes']);

        if ($filter === 'archived') {
            $query->where('is_archived', true);
        } elseif ($filter === 'all') {
            // Show all (both active and archived)
        } else {
            $filter = 'active';
            $query->where('is_archived', false);
        }

        $activeCount = Election::where('is_archived', false)->count();
        $archivedCount = Election::where('is_archived', true)->count();
        $totalCount = Election::count();

        $elections = $query->latest()->paginate(15)->withQueryString();

        return view('admin.elections.index', compact('elections', 'filter', 'activeCount', 'archivedCount', 'totalCount'));
    }

    public function create()
    {
        Gate::authorize('create', Election::class);

        $suggestedPositions = Election::SUGGESTED_POSITIONS;

        return view('admin.elections.create', compact('suggestedPositions'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Election::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', Rule::in([Election::TYPE_ONLINE, Election::TYPE_PHYSICAL])],
            'term' => ['required', 'string', 'max:50'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
        ]);

        $validated['created_by'] = Auth::id();
        $validated['status'] = Election::STATUS_DRAFT;

        $election = Election::create($validated);

        // Optional: add pre-selected initial suggested positions if requested
        if ($request->has('initial_positions') && is_array($request->input('initial_positions'))) {
            $order = 1;
            foreach ($request->input('initial_positions') as $posName) {
                if (trim($posName) !== '') {
                    $election->positions()->create([
                        'name' => trim($posName),
                        'display_order' => $order++,
                    ]);
                }
            }
        }

        AuditLog::create([
            'actor_id' => Auth::id(),
            'target_type' => Election::class,
            'target_id' => $election->id,
            'action' => AuditLog::ACTION_ELECTION_CREATED,
            'details' => "Election '{$election->title}' created as draft.",
        ]);

        return redirect()->route('admin.elections.show', $election)
            ->with('success', 'Election created successfully in draft status.');
    }

    public function show(Election $election)
    {
        Gate::authorize('view', $election);

        $election->load([
            'creator',
            'positions' => fn ($q) => $q->orderBy('display_order')->with([
                'tieResolvedBy',
                'candidates' => fn ($cq) => $cq->with('alumni.batch')->withCount('votes'),
            ]),
            'candidates',
            'committee',
        ]);

        $alumniList = Alumni::with('batch')
            ->where('status', '!=', Alumni::STATUS_INACTIVE)
            ->orderBy('full_name')
            ->get();

        $resultsData = null;
        if (! $election->isDraft()) {
            $resultsData = $this->electionService->getOnlineResults($election);
        }

        return view('admin.elections.show', compact('election', 'alumniList', 'resultsData'));
    }

    public function edit(Election $election)
    {
        Gate::authorize('update', $election);

        $election->loadCount(['votes', 'candidates', 'positions']);

        return view('admin.elections.edit', compact('election'));
    }

    public function update(Request $request, Election $election)
    {
        Gate::authorize('update', $election);

        if ($election->isFinalized()) {
            return back()->with('error', 'Finalized elections are officially certified and locked. Election details cannot be modified.');
        }

        $hasVotes = $election->votes()->exists();

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'term' => ['required', 'string', 'max:50'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
        ];

        // Type can only be altered if no votes exist and election is still draft
        if (! $hasVotes && $election->isDraft()) {
            $rules['type'] = ['required', 'string', Rule::in([Election::TYPE_ONLINE, Election::TYPE_PHYSICAL])];
        }

        $validated = $request->validate($rules);

        if ($hasVotes && $request->filled('type') && $request->input('type') !== $election->type) {
            return back()->withInput()->with('error', 'Cannot change election type after votes have already been cast.');
        }

        $election->update($validated);

        AuditLog::create([
            'actor_id' => Auth::id(),
            'target_type' => Election::class,
            'target_id' => $election->id,
            'action' => AuditLog::ACTION_ELECTION_UPDATED,
            'details' => "Election '{$election->title}' details updated.",
        ]);

        return redirect()->route('admin.elections.show', $election)
            ->with('success', 'Election details updated successfully.');
    }

    public function destroy(Election $election)
    {
        Gate::authorize('delete', $election);

        if ($election->votes()->exists()) {
            $voteCount = $election->votes()->count();
            return redirect()->route('admin.elections.index')
                ->with('error', "Election '{$election->title}' cannot be deleted because it contains {$voteCount} recorded " . \Illuminate\Support\Str::plural('vote', $voteCount) . ". Deletion is disabled to protect voter integrity and audit logs.");
        }

        if ($election->isFinalized()) {
            return redirect()->route('admin.elections.index')
                ->with('error', "Election '{$election->title}' is finalized with certified results and cannot be deleted. Official election history must be preserved.");
        }

        if ($election->isOngoing()) {
            return redirect()->route('admin.elections.index')
                ->with('error', "Election '{$election->title}' cannot be deleted while voting is ongoing. Close or conclude the election first.");
        }

        if ($election->committee()->exists()) {
            return redirect()->route('admin.elections.index')
                ->with('error', "Election '{$election->title}' cannot be deleted because an Executive Committee is linked to it. Committee history must be preserved.");
        }

        $title = $election->title;
        $id = $election->id;

        AuditLog::create([
            'actor_id' => Auth::id(),
            'target_type' => Election::class,
            'target_id' => $id,
            'action' => AuditLog::ACTION_ELECTION_DELETED,
            'details' => "Election '{$title}' (ID: {$id}) was permanently deleted by administrator.",
        ]);

        $election->delete();

        return redirect()->route('admin.elections.index')
            ->with('success', "Election '{$title}' has been deleted.");
    }

    public function archive(Election $election)
    {
        Gate::authorize('update', $election);

        if ($election->isOngoing()) {
            return redirect()->route('admin.elections.index')
                ->with('error', "Election '{$election->title}' cannot be archived while voting is active. Conclude the election before archiving.");
        }

        if ($election->isArchived()) {
            return redirect()->route('admin.elections.index')
                ->with('info', "Election '{$election->title}' is already archived.");
        }

        $election->update(['is_archived' => true]);

        AuditLog::create([
            'actor_id' => Auth::id(),
            'target_type' => Election::class,
            'target_id' => $election->id,
            'action' => AuditLog::ACTION_ELECTION_ARCHIVED,
            'details' => "Election '{$election->title}' (ID: {$election->id}) was archived.",
        ]);

        return redirect()->route('admin.elections.index')
            ->with('success', "Election '{$election->title}' has been archived.");
    }

    public function unarchive(Election $election)
    {
        Gate::authorize('update', $election);

        if (! $election->isArchived()) {
            return redirect()->route('admin.elections.index')
                ->with('info', "Election '{$election->title}' is not archived.");
        }

        $election->update(['is_archived' => false]);

        AuditLog::create([
            'actor_id' => Auth::id(),
            'target_type' => Election::class,
            'target_id' => $election->id,
            'action' => AuditLog::ACTION_ELECTION_UNARCHIVED,
            'details' => "Election '{$election->title}' (ID: {$election->id}) was unarchived.",
        ]);

        return redirect()->route('admin.elections.index')
            ->with('success', "Election '{$election->title}' has been unarchived.");
    }

    public function storePosition(Request $request, Election $election)
    {
        Gate::authorize('update', $election);

        if (! $election->isDraft()) {
            abort(403, 'Positions can only be added when the election is in draft status.');
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('election_positions')->where('election_id', $election->id),
            ],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['display_order'] = $validated['display_order'] ?? ($election->positions()->max('display_order') + 1);

        $position = $election->positions()->create($validated);

        AuditLog::create([
            'actor_id' => Auth::id(),
            'target_type' => Election::class,
            'target_id' => $election->id,
            'action' => AuditLog::ACTION_ELECTION_POSITION_ADDED,
            'details' => "Position '{$position->name}' added to election.",
        ]);

        return back()->with('success', "Position '{$position->name}' added successfully.");
    }

    public function destroyPosition(Election $election, ElectionPosition $position)
    {
        Gate::authorize('update', $election);

        if (! $election->isDraft()) {
            abort(403, 'Positions can only be removed when the election is in draft status.');
        }

        abort_if($position->election_id !== $election->id, 404);

        $name = $position->name;
        $position->delete();

        AuditLog::create([
            'actor_id' => Auth::id(),
            'target_type' => Election::class,
            'target_id' => $election->id,
            'action' => AuditLog::ACTION_ELECTION_POSITION_REMOVED,
            'details' => "Position '{$name}' removed from election.",
        ]);

        return back()->with('success', "Position '{$name}' removed.");
    }

    public function storeCandidate(Request $request, Election $election)
    {
        Gate::authorize('update', $election);

        if (! $election->isDraft()) {
            abort(403, 'Candidates can only be added when the election is in draft status.');
        }

        $validated = $request->validate([
            'election_position_id' => [
                'required',
                Rule::exists('election_positions', 'id')->where('election_id', $election->id),
            ],
            'alumni_id' => [
                'required',
                Rule::exists('alumni', 'id')->where(function ($q) {
                    $q->where('status', '!=', Alumni::STATUS_INACTIVE);
                }),
                Rule::unique('election_candidates')->where('election_id', $election->id),
            ],
            'statement' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'max:2048', 'mimes:jpeg,png,jpg,webp'],
        ], [
            'alumni_id.unique' => 'This alumnus is already registered as a candidate in this election.',
            'alumni_id.exists' => 'The selected alumni is not eligible or not found in the system.',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('candidates', 'public');
        }

        $candidate = $election->candidates()->create([
            'election_position_id' => $validated['election_position_id'],
            'alumni_id' => $validated['alumni_id'],
            'statement' => $validated['statement'] ?? null,
            'photo_path' => $photoPath,
            'is_winner' => false,
        ]);

        $alumni = Alumni::find($validated['alumni_id']);

        AuditLog::create([
            'actor_id' => Auth::id(),
            'target_type' => Election::class,
            'target_id' => $election->id,
            'action' => AuditLog::ACTION_ELECTION_CANDIDATE_ADDED,
            'details' => "Added candidate {$alumni->name} for position '{$candidate->position->name}'.",
        ]);

        return back()->with('success', "Candidate {$alumni->name} added successfully.");
    }

    public function updateCandidate(Request $request, Election $election, ElectionCandidate $candidate)
    {
        Gate::authorize('update', $election);

        if (! $election->isDraft()) {
            abort(403, 'Candidates can only be edited when the election is in draft status.');
        }

        abort_if($candidate->election_id !== $election->id, 404);

        $validated = $request->validate([
            'election_position_id' => [
                'required',
                Rule::exists('election_positions', 'id')->where('election_id', $election->id),
            ],
            'statement' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'max:2048', 'mimes:jpeg,png,jpg,webp'],
        ]);

        $photoPath = $candidate->photo_path;
        if ($request->hasFile('photo')) {
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }
            $photoPath = $request->file('photo')->store('candidates', 'public');
        }

        $candidate->update([
            'election_position_id' => $validated['election_position_id'],
            'statement' => $validated['statement'] ?? null,
            'photo_path' => $photoPath,
        ]);

        $name = $candidate->alumni?->name ?? 'Candidate';

        AuditLog::create([
            'actor_id' => Auth::id(),
            'target_type' => Election::class,
            'target_id' => $election->id,
            'action' => AuditLog::ACTION_ELECTION_CANDIDATE_UPDATED,
            'details' => "Updated candidate {$name} for position '{$candidate->position->name}'.",
        ]);

        return back()->with('success', "Candidate {$name} updated successfully.");
    }

    public function destroyCandidate(Election $election, ElectionCandidate $candidate)
    {
        Gate::authorize('update', $election);

        if (! $election->isDraft()) {
            abort(403, 'Candidates can only be removed when the election is in draft status.');
        }

        abort_if($candidate->election_id !== $election->id, 404);

        $name = $candidate->alumni?->name ?? 'Candidate';

        if ($candidate->photo_path) {
            Storage::disk('public')->delete($candidate->photo_path);
        }

        $candidate->delete();

        AuditLog::create([
            'actor_id' => Auth::id(),
            'target_type' => Election::class,
            'target_id' => $election->id,
            'action' => AuditLog::ACTION_ELECTION_CANDIDATE_REMOVED,
            'details' => "Removed candidate {$name} from election.",
        ]);

        return back()->with('success', "Candidate {$name} removed.");
    }

    public function start(Election $election)
    {
        Gate::authorize('manage', $election);

        try {
            $this->electionService->startElection($election, Auth::user());
            return back()->with('success', 'Election has been started and is now ongoing.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first() ?? 'Unable to start election.';
            return back()->withErrors($e->errors())->with('error', $firstError);
        }
    }

    public function close(Election $election)
    {
        Gate::authorize('manage', $election);

        try {
            $this->electionService->closeElection($election, Auth::user());
            return back()->with('success', 'Election has been closed.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first() ?? 'Unable to close election.';
            return back()->withErrors($e->errors())->with('error', $firstError);
        }
    }

    public function resolveTie(Request $request, Election $election)
    {
        Gate::authorize('manage', $election);

        $validated = $request->validate([
            'position_id' => ['required', 'exists:election_positions,id'],
            'candidate_id' => ['required', 'exists:election_candidates,id'],
            'resolution_method' => ['nullable', 'string', 'max:100'],
            'resolution_reason' => ['nullable', 'string', 'max:1000'],
            'confirm_resolution' => ['sometimes', 'accepted'],
        ]);

        $position = ElectionPosition::findOrFail($validated['position_id']);
        $candidate = ElectionCandidate::findOrFail($validated['candidate_id']);
        $method = $validated['resolution_method'] ?? 'committee_consensus';
        $reason = $validated['resolution_reason'] ?? null;

        try {
            $this->electionService->resolveTie($election, $position, $candidate, Auth::user(), $method, $reason);
            return back()->with('success', "Tie resolved for '{$position->name}'. Winner recorded: {$candidate->alumni->name}.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first() ?? 'Unable to resolve tie.';
            return back()->withErrors($e->errors())->with('error', $firstError);
        }
    }

    public function selectPhysicalWinner(Request $request, Election $election)
    {
        Gate::authorize('manage', $election);

        $validated = $request->validate([
            'position_id' => ['required', 'exists:election_positions,id'],
            'candidate_id' => ['required', 'exists:election_candidates,id'],
        ]);

        $position = ElectionPosition::findOrFail($validated['position_id']);
        $candidate = ElectionCandidate::findOrFail($validated['candidate_id']);

        $this->electionService->selectPhysicalWinner($election, $position, $candidate, Auth::user());

        return back()->with('success', "Winner selected for '{$position->name}': {$candidate->alumni->name}.");
    }

    public function finalize(Election $election)
    {
        Gate::authorize('finalize', $election);

        try {
            $committee = $this->electionService->finalizeElection($election, Auth::user());
            return back()->with('success', "Election finalized successfully! Executive Committee for term '{$committee->term}' has been created and set as current.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first() ?? 'Unable to finalize election.';
            return back()->withErrors($e->errors())->with('error', $firstError);
        }
    }
}
