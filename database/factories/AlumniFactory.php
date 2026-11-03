<?php

namespace Database\Factories;

use App\Models\Alumni;
use App\Models\Batch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alumni>
 */
class AlumniFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => $this->faker->unique()->numerify('ICT-####-##'),
            'full_name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'batch_id' => Batch::factory(),
            'session' => null,
            'graduation_year' => $this->faker->numberBetween(2015, 2024),
            'profile_photo' => null,
            'phone' => $this->faker->optional()->phoneNumber(),
            'user_id' => null,
            'status' => 'unclaimed',
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'verification_status' => 'verified',
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'verification_status' => 'verified',
            'alumni_id' => Alumni::generateNextAlumniId(),
            'qr_token' => Alumni::generateQrToken(),
            'verified_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'pending',
        ]);
    }

    public function rejected(?string $reason = 'Information mismatch'): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'rejected',
            'rejection_reason' => $reason,
            'rejected_at' => now(),
        ]);
    }
}
