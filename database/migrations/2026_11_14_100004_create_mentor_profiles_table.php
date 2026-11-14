<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mentor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('alumni_id')->constrained('alumni')->cascadeOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected', 'suspended'])->default('pending');

            // Professional information
            $table->string('current_designation');
            $table->string('company');
            $table->string('industry')->nullable();
            $table->string('country');
            $table->string('city');
            $table->unsignedTinyInteger('years_of_experience')->default(1);
            $table->string('linkedin_url')->nullable();
            $table->text('bio');

            // Mentorship preferences & capacity
            $table->text('expertise_areas')->nullable();
            $table->text('topics_offered')->nullable();
            $table->enum('preferred_mentee_audience', ['students', 'graduates', 'both'])->default('both');
            $table->enum('mentorship_mode', ['online', 'offline', 'both'])->default('both');
            $table->string('preferred_language')->default('Both');
            $table->text('availability_schedule')->nullable();
            $table->unsignedTinyInteger('max_mentees')->default(3);
            $table->enum('mentorship_frequency', ['one_time', 'weekly', 'biweekly', 'monthly', 'flexible'])->default('flexible');
            $table->text('personal_statement')->nullable();

            // Operational flags
            $table->boolean('is_paused')->default(false);
            $table->text('rejection_reason')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('suspended_at')->nullable();
            $table->foreignId('suspended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('is_paused');
            $table->index('current_designation');
            $table->index('company');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentor_profiles');
    }
};
