<?php

namespace Database\Factories;

use App\Models\Election;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Election>
 */
class ElectionFactory extends Factory
{
    protected $model = Election::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3).' Election',
            'description' => fake()->paragraph(),
            'type' => Election::TYPE_ONLINE,
            'term' => '2026-2028',
            'start_time' => now()->addDay(),
            'end_time' => now()->addDays(3),
            'created_by' => User::factory(),
            'status' => Election::STATUS_DRAFT,
            'finalized_at' => null,
        ];
    }

    public function physical(): static
    {
        return $this->state(fn () => [
            'type' => Election::TYPE_PHYSICAL,
        ]);
    }

    public function ongoing(): static
    {
        return $this->state(fn () => [
            'status' => Election::STATUS_ONGOING,
            'start_time' => now()->subHour(),
            'end_time' => now()->addHours(24),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => Election::STATUS_CLOSED,
            'start_time' => now()->subDays(2),
            'end_time' => now()->subHour(),
        ]);
    }

    public function finalized(): static
    {
        return $this->state(fn () => [
            'status' => Election::STATUS_FINALIZED,
            'start_time' => now()->subDays(3),
            'end_time' => now()->subDay(),
            'finalized_at' => now(),
        ]);
    }
}
