<?php

namespace Database\Factories;

use App\Models\Album;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlbumFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'batch_id' => null,
            'event_id' => null,
            'status' => Album::STATUS_DRAFT,
        ];
    }

    public function forBatch(int $batchId): static
    {
        return $this->state(fn () => ['batch_id' => $batchId]);
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => Album::STATUS_PUBLISHED]);
    }
}
