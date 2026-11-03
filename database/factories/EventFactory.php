<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'event_type' => 'Reunion',
            'event_date' => now()->addMonth(),
            'venue' => $this->faker->address(),
            'status' => Event::STATUS_DRAFT,
            'batch_id' => null,
        ];
    }

    public function forBatch(int $batchId): static
    {
        return $this->state(fn () => ['batch_id' => $batchId]);
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => Event::STATUS_PUBLISHED]);
    }
}
