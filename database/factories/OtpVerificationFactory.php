<?php

namespace Database\Factories;

use App\Models\OtpVerification;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<OtpVerification>
 */
class OtpVerificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'otp_code' => (string) $this->faker->numberBetween(100000, 999999),
            'purpose' => 'registration',
            'expires_at' => Carbon::now()->addMinutes(10),
            'is_verified' => false,
            'attempts' => 0,
        ];
    }
}
