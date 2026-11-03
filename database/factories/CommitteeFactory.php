<?php

namespace Database\Factories;

use App\Models\Committee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Committee>
 */
class CommitteeFactory extends Factory
{
    protected $model = Committee::class;

    public function definition(): array
    {
        return [
            'term' => '2026-2028',
            'is_current' => false,
            'election_id' => null,
            'created_by' => User::factory(),
        ];
    }

    public function current(): static
    {
        return $this->state(fn () => [
            'is_current' => true,
        ]);
    }
}
