<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Committee;
use App\Models\CommitteeMember;
use App\Models\Election;
use App\Models\ElectionCandidate;
use App\Models\ElectionPosition;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ElectionService
{
    public function __construct(
        protected ElectionNotifier $notifier
    ) {}

    public function startElection(Election $election, User $actor): void
    {
        if (! $election->isDraft()) {
            throw ValidationException::withMessages([
                'status' => 'Only draft elections can be started.',
            ]);
        }

        if ($election->positions()->count() === 0) {
            throw ValidationException::withMessages([
                'positions' => 'Election must have at least one position before starting.',
            ]);
        }

        $missingPositions = [];
        foreach ($election->positions as $position) {
            if ($position->candidates()->count() === 0) {
                $missingPositions[] = $position->name;
            }
        }

        if (! empty($missingPositions)) {
            $positionsList = implode(', ', $missingPositions);
            throw ValidationException::withMessages([
                'candidates' => "Every position must have at least one candidate before starting the election. Missing candidate(s) for: {$positionsList}.",
            ]);
        }

        DB::transaction(function () use ($election, $actor) {
            $election->update([
                'status' => Election::STATUS_ONGOING,
            ]);

            AuditLog::create([
                'actor_id' => $actor->id,
                'target_type' => Election::class,
                'target_id' => $election->id,
                'action' => AuditLog::ACTION_ELECTION_OPENED,
                'details' => "Election '{$election->title}' opened for voting.",
            ]);
        });

        // Notify eligible alumni if election is online
        if ($election->isOnline()) {
            $this->notifier->notifyElectionOpened($election, $actor);
        }
    }

    public function closeElection(Election $election, User $actor): void
    {
        if (! $election->isOngoing()) {
            throw ValidationException::withMessages([
                'status' => 'Only ongoing elections can be closed.',
            ]);
        }

        DB::transaction(function () use ($election, $actor) {
            $election->update([
                'status' => Election::STATUS_CLOSED,
            ]);

            AuditLog::create([
                'actor_id' => $actor->id,
                'target_type' => Election::class,
                'target_id' => $election->id,
                'action' => AuditLog::ACTION_ELECTION_CLOSED,
                'details' => "Election '{$election->title}' closed.",
            ]);

            // For online elections, automatically determine unambiguous winners
            if ($election->isOnline()) {
                $this->determineOnlineWinners($election);
            }
        });
    }

    public function determineOnlineWinners(Election $election): void
    {
        foreach ($election->positions()->with('candidates')->get() as $position) {
            // If this position was legitimately resolved via tie-break, preserve it
            if ($position->isTieResolved() && $position->candidates()->where('is_winner', true)->exists()) {
                continue;
            }

            $candidates = $position->candidates()->withCount('votes')->get();

            if ($candidates->isEmpty()) {
                continue;
            }

            $maxVotes = $candidates->max('votes_count');
            $leaders = $candidates->where('votes_count', $maxVotes);

            // If there's an unambiguous winner (single candidate with max votes)
            if ($leaders->count() === 1) {
                $winner = $leaders->first();
                $position->candidates()->where('id', '!=', $winner->id)->update(['is_winner' => false]);
                $position->candidates()->where('id', $winner->id)->update(['is_winner' => true]);
            } else {
                // In case of a tie, leave all as false so tie-resolution is required
                $position->candidates()->update(['is_winner' => false]);
            }
        }
    }

    public function getOnlineResults(Election $election): array
    {
        $results = [];
        $hasTies = false;
        $tiedPositions = [];

        foreach ($election->positions as $position) {
            $candidates = $position->candidates()
                ->with('alumni')
                ->withCount('votes')
                ->orderByDesc('votes_count')
                ->get();

            $maxVotes = $candidates->max('votes_count');
            $leaders = $candidates->where('votes_count', $maxVotes);
            $winner = $candidates->firstWhere('is_winner', true);

            $isTied = false;
            $isResolvedTie = false;

            // Tied if multiple candidates share the max votes
            if ($leaders->count() > 1) {
                if ($position->isTieResolved() && $winner) {
                    $isTied = true;
                    $isResolvedTie = true;
                } else {
                    $isTied = true;
                    $hasTies = true;
                    $tiedPositions[] = $position;
                    // If tied and not legitimately resolved, winner MUST NOT be shown as winner
                    $winner = null;
                }
            }

            $results[] = [
                'position' => $position,
                'candidates' => $candidates,
                'max_votes' => $maxVotes,
                'leaders' => $leaders,
                'is_tied' => $isTied,
                'is_resolved_tie' => $isResolvedTie,
                'winner' => $winner,
            ];
        }

        return [
            'results' => $results,
            'has_ties' => $hasTies,
            'tied_positions' => $tiedPositions,
        ];
    }

    public function resolveTie(
        Election $election,
        ElectionPosition $position,
        ElectionCandidate $winner,
        User $actor,
        string $method = 'committee_consensus',
        ?string $reason = null
    ): void {
        if ($election->isFinalized()) {
            throw ValidationException::withMessages([
                'election' => 'Finalized elections cannot be modified.',
            ]);
        }

        if ($position->election_id !== $election->id) {
            throw ValidationException::withMessages([
                'position' => 'Position does not belong to this election.',
            ]);
        }

        if ($winner->election_position_id !== $position->id || $winner->election_id !== $election->id) {
            throw ValidationException::withMessages([
                'winner' => 'Candidate does not belong to this position.',
            ]);
        }

        // Validate that this position is actually tied for top votes
        $candidates = $position->candidates()->withCount('votes')->get();
        $maxVotes = $candidates->max('votes_count');
        $leaders = $candidates->where('votes_count', $maxVotes);

        if ($leaders->count() <= 1) {
            throw ValidationException::withMessages([
                'position' => "Position '{$position->name}' does not have a tied vote count.",
            ]);
        }

        // Validate that the selected candidate is genuinely tied for the highest vote count
        $winnerWithVotes = $candidates->firstWhere('id', $winner->id);
        if (! $winnerWithVotes || $winnerWithVotes->votes_count !== $maxVotes) {
            throw ValidationException::withMessages([
                'candidate' => "Only a candidate tied for the highest vote count ({$maxVotes} votes) can be selected to break the tie.",
            ]);
        }

        DB::transaction(function () use ($election, $position, $winner, $actor, $method, $reason) {
            $position->candidates()->update(['is_winner' => false]);
            $winner->update(['is_winner' => true]);

            $position->update([
                'tie_resolved_at' => Carbon::now(),
                'tie_resolved_by' => $actor->id,
                'tie_resolution_method' => $method ?: 'committee_consensus',
                'tie_resolution_reason' => $reason ?: 'Resolved by authorized election administrator.',
            ]);

            $methodLabel = match ($method) {
                'runoff_outcome' => 'Runoff Election Outcome',
                'lottery_draw' => 'Lottery / Random Draw',
                'academic_merit' => 'Academic Merit / Seniority',
                'committee_consensus' => 'Executive Committee Consensus',
                default => ucfirst(str_replace('_', ' ', $method)),
            };

            AuditLog::create([
                'actor_id' => $actor->id,
                'target_type' => Election::class,
                'target_id' => $election->id,
                'action' => AuditLog::ACTION_ELECTION_TIE_RESOLVED,
                'details' => "Resolved tie for '{$position->name}': winner selected is {$winner->alumni->name} via method '{$methodLabel}'. Reason: " . ($reason ?: 'Administrative resolution.'),
            ]);
        });
    }

    public function selectPhysicalWinner(Election $election, ElectionPosition $position, ElectionCandidate $winner, User $actor): void
    {
        if (! $election->isPhysical()) {
            throw ValidationException::withMessages([
                'type' => 'Manual winner selection is only for physical elections.',
            ]);
        }

        if ($election->isFinalized()) {
            throw ValidationException::withMessages([
                'election' => 'Finalized elections cannot be modified.',
            ]);
        }

        if ($position->election_id !== $election->id) {
            throw ValidationException::withMessages([
                'position' => 'Position does not belong to this election.',
            ]);
        }

        if ($winner->election_position_id !== $position->id || $winner->election_id !== $election->id) {
            throw ValidationException::withMessages([
                'winner' => 'Candidate does not belong to this position.',
            ]);
        }

        DB::transaction(function () use ($election, $position, $winner, $actor) {
            $position->candidates()->where('id', '!=', $winner->id)->update(['is_winner' => false]);
            $position->candidates()->where('id', $winner->id)->update(['is_winner' => true]);

            AuditLog::create([
                'actor_id' => $actor->id,
                'target_type' => Election::class,
                'target_id' => $election->id,
                'action' => AuditLog::ACTION_PHYSICAL_WINNER_SELECTED,
                'details' => "Physical winner for '{$position->name}': selected {$winner->alumni->name}.",
            ]);
        });
    }

    public function finalizeElection(Election $election, User $actor): Committee
    {
        if ($election->isFinalized()) {
            throw ValidationException::withMessages([
                'status' => 'Election has already been finalized.',
            ]);
        }

        if (! $election->isClosed()) {
            throw ValidationException::withMessages([
                'status' => 'Election must be closed before finalization.',
            ]);
        }

        // For online elections: ensure any auto-winners are set if unambiguous
        if ($election->isOnline()) {
            $this->determineOnlineWinners($election);
        }

        // Check each position has a winner and that no online position is left in an unresolved tie
        foreach ($election->positions()->with(['candidates.votes'])->get() as $position) {
            $candidates = $position->candidates()->withCount('votes')->get();
            $maxVotes = $candidates->max('votes_count');
            $leaders = $candidates->where('votes_count', $maxVotes);

            if ($election->isOnline() && $leaders->count() > 1 && ! $position->isTieResolved()) {
                throw ValidationException::withMessages([
                    'finalization' => "Position '{$position->name}' has tied or unresolved results. Please resolve the tie before finalizing.",
                ]);
            }

            $hasWinner = $position->candidates()->where('is_winner', true)->exists();
            if (! $hasWinner) {
                if ($election->isOnline()) {
                    throw ValidationException::withMessages([
                        'finalization' => "Position '{$position->name}' has tied or unresolved results. Please resolve the tie before finalizing.",
                    ]);
                } else {
                    throw ValidationException::withMessages([
                        'finalization' => "Position '{$position->name}' has no selected winner. Please select a winner before finalizing.",
                    ]);
                }
            }
        }

        return DB::transaction(function () use ($election, $actor) {
            $election->update([
                'status' => Election::STATUS_FINALIZED,
                'finalized_at' => Carbon::now(),
            ]);

            // Deactivate existing current committees
            Committee::where('is_current', true)->update(['is_current' => false]);

            // Create or retrieve committee (idempotency guard)
            $committee = Committee::firstOrCreate(
                ['election_id' => $election->id],
                [
                    'term' => $election->term,
                    'is_current' => true,
                    'created_by' => $actor->id,
                ]
            );

            $committee->update(['is_current' => true, 'term' => $election->term]);

            // Remove existing members if previously populated
            $committee->members()->delete();

            // Create committee members from position winners
            foreach ($election->positions as $position) {
                $winner = $position->candidates()->where('is_winner', true)->first();
                if ($winner) {
                    CommitteeMember::create([
                        'committee_id' => $committee->id,
                        'alumni_id' => $winner->alumni_id,
                        'position_name' => $position->name,
                        'display_order' => $position->display_order,
                    ]);
                }
            }

            AuditLog::create([
                'actor_id' => $actor->id,
                'target_type' => Election::class,
                'target_id' => $election->id,
                'action' => AuditLog::ACTION_ELECTION_FINALIZED,
                'details' => "Election '{$election->title}' finalized. Executive Committee for term '{$committee->term}' created.",
            ]);

            return $committee;
        });
    }
}
