<?php

namespace Database\Factories;

use App\Models\Album;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhotoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'album_id' => Album::factory(),
            'image_path' => 'gallery-photos/fake-'.$this->faker->uuid().'.jpg',
            'caption' => $this->faker->sentence(4),
        ];
    }
}
