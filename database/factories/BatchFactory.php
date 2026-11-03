<?php

namespace Database\Factories;

use App\Models\Batch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Batch>
 */
class BatchFactory extends Factory
{
    public function definition(): array
    {
        // Unique per call via Faker's own uniqueness tracking (reset
        // automatically between tests), rather than a static counter
        // that persisted state across the whole test run.
        $number = $this->faker->unique()->numberBetween(1, 9000);
        $code = str_pad((string) $number, 2, '0', STR_PAD_LEFT);
        $startYear = 2010 + $number;

        return [
            'batch_name' => "Batch {$code}",
            'batch_code' => "B{$code}",
            'session' => $startYear.'-'.($startYear + 1),
            'graduation_year' => $startYear + 4,
            'description' => null,
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
