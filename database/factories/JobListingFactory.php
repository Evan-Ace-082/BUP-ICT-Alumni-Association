<?php

namespace Database\Factories;

use App\Models\JobListing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobListingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'posted_by' => User::factory(),
            'title' => $this->faker->jobTitle(),
            'company' => $this->faker->company(),
            'description' => $this->faker->paragraph(),
            'employment_type' => 'Full Time',
            'location' => $this->faker->city(),
            'country' => 'Bangladesh',
            'salary' => null,
            'application_url' => null,
            'application_email' => null,
            'deadline' => null,
            'status' => JobListing::STATUS_PUBLISHED,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => ['status' => JobListing::STATUS_CLOSED]);
    }
}
