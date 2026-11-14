<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            BatchSeeder::class,
            AchievementCategorySeeder::class,
            MentorshipCategorySeeder::class,
        ]);

        // Development-only test account. Not created in environments
        // where APP_ENV is production. firstOrCreate keeps this safe to
        // re-run (php artisan db:seed) without a duplicate-email error.
        if (! app()->environment('production')) {
            User::firstOrCreate(
                ['email' => 'admin@example.test'],
                ['name' => 'Development Admin', 'password' => bcrypt('password')]
            );

            $batch = \App\Models\Batch::first();
            if ($batch) {
                \App\Models\Alumni::firstOrCreate(
                    ['student_id' => '160101'],
                    [
                        'full_name' => 'Nabil Hasan Evan',
                        'email' => 'evan@example.test',
                        'batch_id' => $batch->id,
                        'session' => '2016-2017',
                        'graduation_year' => 2020,
                        'status' => \App\Models\Alumni::STATUS_ACTIVE,
                    ]
                );

                \App\Models\Alumni::firstOrCreate(
                    ['student_id' => '160102'],
                    [
                        'full_name' => 'Tasin Ahmed',
                        'email' => 'tasin@example.test',
                        'batch_id' => $batch->id,
                        'session' => '2016-2017',
                        'graduation_year' => 2020,
                        'status' => \App\Models\Alumni::STATUS_ACTIVE,
                    ]
                );
            }
        }
    }
}
