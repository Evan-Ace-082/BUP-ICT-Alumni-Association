<?php

namespace Database\Seeders;

use App\Models\AchievementCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AchievementCategorySeeder extends Seeder
{
    public const DEFAULT_CATEGORIES = [
        'Banking & Finance',
        'Research & Academia',
        'BCS & Government',
        'Teaching & Education',
        'Corporate & Industry',
        'International & Higher Studies',
        'Entrepreneurship',
        'Others',
    ];

    public function run(): void
    {
        foreach (self::DEFAULT_CATEGORIES as $name) {
            AchievementCategory::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => "Official achievement category for {$name}.",
                    'status' => AchievementCategory::STATUS_APPROVED,
                    'is_active' => true,
                    'suggested_by' => null,
                ]
            );
        }
    }
}
