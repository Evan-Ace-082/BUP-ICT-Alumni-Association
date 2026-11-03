<?php

namespace Database\Factories;

use App\Models\AchievementCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AchievementCategoryFactory extends Factory
{
    protected $model = AchievementCategory::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(),
            'status' => AchievementCategory::STATUS_APPROVED,
            'is_active' => true,
            'suggested_by' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => AchievementCategory::STATUS_PENDING,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
