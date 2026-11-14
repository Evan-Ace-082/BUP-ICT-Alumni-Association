<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mentorship_settings', function (Blueprint $table) {
            $table->id();
            $table->string('cohort_mode')->default('manual'); // manual | automatic
            $table->unsignedSmallInteger('latest_graduation_year')->nullable()->default(2024);
            $table->boolean('mentorship_enabled')->default(true);
            $table->unsignedSmallInteger('max_active_requests_per_mentee')->default(3);
            $table->timestamps();
        });

        DB::table('mentorship_settings')->insert([
            'cohort_mode' => 'manual',
            'latest_graduation_year' => 2024,
            'mentorship_enabled' => true,
            'max_active_requests_per_mentee' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('mentorship_settings');
    }
};
