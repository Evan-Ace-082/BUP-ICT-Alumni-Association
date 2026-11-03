<?php

namespace Database\Factories;

use App\Models\Alumni;
use App\Models\Election;
use App\Models\ElectionCandidate;
use App\Models\ElectionPosition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ElectionCandidate>
 */
class ElectionCandidateFactory extends Factory
{
    protected $model = ElectionCandidate::class;

    public function definition(): array
    {
        return [
            'election_id' => Election::factory(),
            'election_position_id' => ElectionPosition::factory(),
            'alumni_id' => Alumni::factory(),
            'statement' => fake()->paragraph(),
            'photo_path' => null,
            'is_winner' => false,
        ];
    }
}
