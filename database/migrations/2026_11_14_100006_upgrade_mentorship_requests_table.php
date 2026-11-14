<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mentorship_requests', function (Blueprint $table) {
            $table->foreignId('mentor_profile_id')->nullable()->after('mentor_id')->constrained('mentor_profiles')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->after('mentor_profile_id')->constrained('mentorship_categories')->nullOnDelete();
            $table->string('specific_topic')->nullable()->after('category_id');
            $table->text('reason')->nullable()->after('specific_topic');
            $table->text('career_goals')->nullable()->after('reason');
            $table->string('preferred_mode')->default('online')->after('career_goals');
            $table->string('preferred_schedule')->nullable()->after('preferred_mode');
            $table->text('decline_reason')->nullable()->after('status');
            $table->timestamp('completed_at')->nullable()->after('responded_at');
            $table->foreignId('completed_by')->nullable()->after('completed_at')->constrained('users')->nullOnDelete();
            $table->text('feedback')->nullable()->after('completed_by');
            $table->unsignedTinyInteger('rating')->nullable()->after('feedback');
        });
    }

    public function down(): void
    {
        Schema::table('mentorship_requests', function (Blueprint $table) {
            $table->dropForeign(['mentor_profile_id']);
            $table->dropForeign(['category_id']);
            $table->dropForeign(['completed_by']);
            $table->dropColumn([
                'mentor_profile_id',
                'category_id',
                'specific_topic',
                'reason',
                'career_goals',
                'preferred_mode',
                'preferred_schedule',
                'decline_reason',
                'completed_at',
                'completed_by',
                'feedback',
                'rating',
            ]);
        });
    }
};
