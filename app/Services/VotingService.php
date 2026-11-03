<?php

namespace App\Services;

use App\Models\Election;
use App\Models\ElectionCandidate;
use App\Models\ElectionPosition;
use App\Models\ElectionVote;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VotingService
{
    public function castVote(
        Election $election,
        ElectionPosition $position,
        ElectionCandidate $candidate,
        User $voter
    ): ElectionVote {
        // 1. Account status validation
        if ($voter->status !== User::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'voter' => 'Your account is suspended or inactive.',
            ]);
        }

        // 2. Linked alumni validation
        if (! $voter->alumni) {
            throw ValidationException::withMessages([
                'voter' => 'You must have a linked alumni profile to vote.',
            ]);
        }

        // 3. Alumni eligibility check
        if (! $voter->alumni->canLogIn()) {
            throw ValidationException::withMessages([
                'voter' => 'Your alumni profile is not verified or approved for voting.',
            ]);
        }

        // 4. Online type check
        if (! $election->isOnline()) {
            throw ValidationException::withMessages([
                'election' => 'Voting is not supported for physical elections.',
            ]);
        }

        // 5. Election status check
        if (! $election->isOngoing()) {
            throw ValidationException::withMessages([
                'election' => 'This election is not currently open for voting.',
            ]);
        }

        // 6. Voting window check
        $now = Carbon::now();
        if ($now->lt($election->start_time) || $now->gt($election->end_time)) {
            throw ValidationException::withMessages([
                'election' => 'Voting is currently outside the scheduled voting window.',
            ]);
        }

        // 7. Position belonging check
        if ($position->election_id !== $election->id) {
            throw ValidationException::withMessages([
                'position' => 'The position does not belong to this election.',
            ]);
        }

        // 8. Candidate belonging check
        if ($candidate->election_position_id !== $position->id || $candidate->election_id !== $election->id) {
            throw ValidationException::withMessages([
                'candidate' => 'The selected candidate does not belong to this position or election.',
            ]);
        }

        // 9. Existing vote check
        $alreadyVoted = ElectionVote::where('election_id', $election->id)
            ->where('election_position_id', $position->id)
            ->where('voter_id', $voter->id)
            ->exists();

        if ($alreadyVoted) {
            throw ValidationException::withMessages([
                'vote' => 'You have already cast your vote for this position.',
            ]);
        }

        // 10. Database transaction with concurrency protection
        try {
            return DB::transaction(function () use ($election, $position, $candidate, $voter) {
                return ElectionVote::create([
                    'election_id' => $election->id,
                    'election_position_id' => $position->id,
                    'election_candidate_id' => $candidate->id,
                    'voter_id' => $voter->id,
                ]);
            });
        } catch (QueryException $e) {
            // Unique constraint violation (code 23000 / 1062) for concurrent double submissions
            throw ValidationException::withMessages([
                'vote' => 'You have already cast your vote for this position.',
            ]);
        }
    }

    public function hasVotedForPosition(Election $election, ElectionPosition $position, User $voter): bool
    {
        return ElectionVote::where('election_id', $election->id)
            ->where('election_position_id', $position->id)
            ->where('voter_id', $voter->id)
            ->exists();
    }

    public function getVoterCastPositionIds(Election $election, User $voter): array
    {
        return ElectionVote::where('election_id', $election->id)
            ->where('voter_id', $voter->id)
            ->pluck('election_position_id')
            ->toArray();
    }
}
