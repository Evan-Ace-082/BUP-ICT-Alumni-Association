<?php

namespace Database\Factories;

use App\Models\MentorshipRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MentorshipRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mentor_id' => User::factory(),
            'mentee_id' => User::factory(),
            'message' => $this->faker->sentence(),
            'status' => MentorshipRequest::STATUS_PENDING,
            'responded_at' => null,
        ];
    }
}
