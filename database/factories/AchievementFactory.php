<?php

namespace Database\Factories;

use App\Models\Achievement;
use App\Models\Alumni;
use Illuminate\Database\Eloquent\Factories\Factory;

class AchievementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'alumni_id' => Alumni::factory(),
            'achievement_category_id' => fn () => \App\Models\AchievementCategory::first()?->id ?? \App\Models\AchievementCategory::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'achievement_type' => 'Career Achievement',
            'achievement_date' => now()->subMonths(2),
            'organization' => $this->faker->company(),
            'image' => null,
            'status' => Achievement::STATUS_PENDING,
            'approved_by' => null,
            'approved_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => Achievement::STATUS_APPROVED, 'approved_at' => now()]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => Achievement::STATUS_REJECTED, 'approved_at' => now()]);
    }
}
