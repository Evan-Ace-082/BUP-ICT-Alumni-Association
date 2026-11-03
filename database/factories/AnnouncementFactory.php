<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(6),
            'body' => $this->faker->paragraph(),
            'target_type' => Announcement::TARGET_ALL,
            'batch_id' => null,
            'created_by' => User::factory(),
            'is_public' => false,
            'status' => Announcement::STATUS_DRAFT,
            'published_at' => null,
        ];
    }

    public function forBatch(int $batchId): static
    {
        return $this->state(fn (array $attributes) => [
            'target_type' => Announcement::TARGET_BATCH,
            'batch_id' => $batchId,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Announcement::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }
}
