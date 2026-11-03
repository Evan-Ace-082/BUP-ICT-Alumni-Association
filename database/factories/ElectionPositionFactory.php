<?php

namespace Database\Factories;

use App\Models\Election;
use App\Models\ElectionPosition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ElectionPosition>
 */
class ElectionPositionFactory extends Factory
{
    protected $model = ElectionPosition::class;

    public function definition(): array
    {
        return [
            'election_id' => Election::factory(),
            'name' => fake()->unique()->randomElement(Election::SUGGESTED_POSITIONS),
            'display_order' => 1,
        ];
    }
}
