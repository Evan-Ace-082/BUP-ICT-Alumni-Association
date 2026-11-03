<?php

namespace Database\Factories;

use App\Models\Election;
use App\Models\ElectionCandidate;
use App\Models\ElectionPosition;
use App\Models\ElectionVote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ElectionVote>
 */
class ElectionVoteFactory extends Factory
{
    protected $model = ElectionVote::class;

    public function definition(): array
    {
        return [
            'election_id' => Election::factory(),
            'election_position_id' => ElectionPosition::factory(),
            'election_candidate_id' => ElectionCandidate::factory(),
            'voter_id' => User::factory(),
        ];
    }
}
