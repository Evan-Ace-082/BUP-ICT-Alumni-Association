<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * target_type/status are plain strings validated at the app layer
     * (Announcement::TARGET_TYPES / STATUSES), matching this project's
     * established convention of avoiding DB-level enums (see
     * Alumni::JOB_STATUSES from Phase 4).
     *
     * is_public is deliberately independent from target_type: a
     * target_type='all' announcement is always guest-visible by
     * definition, while a target_type='batch' announcement is guest
     * -visible only when is_public is explicitly true (e.g. a batch
     * reunion event an admin wants publicly advertised). See
     * Announcement::scopeVisibleToGuest().
     */
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('target_type'); // 'all' | 'batch'
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_public')->default(false);
            $table->string('status')->default('draft'); // 'draft' | 'published'
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
