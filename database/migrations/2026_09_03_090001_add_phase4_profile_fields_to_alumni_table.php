<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 4 profile + directory-search fields. All nullable so every
     * existing imported alumni record remains valid without a backfill.
     * `skills` is stored as plain comma-separated text (no separate
     * skills table per the confirmed Phase 4 scope). `mentorship` is a
     * boolean: true = willing to mentor, false = not currently
     * offering it. `job_status` is a free string constrained at the
     * application layer (Alumni::JOB_STATUSES), not a DB enum, so the
     * allowed list can change without a migration later.
     */
    public function up(): void
    {
        Schema::table('alumni', function (Blueprint $table) {
            $table->string('country')->nullable()->after('address');
            $table->string('city')->nullable()->after('country');
            $table->text('skills')->nullable()->after('higher_studies');
            $table->string('job_status')->nullable()->after('skills');
            $table->boolean('mentorship')->nullable()->after('job_status');
        });
    }

    public function down(): void
    {
        Schema::table('alumni', function (Blueprint $table) {
            $table->dropColumn(['country', 'city', 'skills', 'job_status', 'mentorship']);
        });
    }
};
