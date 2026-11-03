<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A Batch Representative must be associated with exactly one
     * batch (Phase 3 Step 2). User::assignedBatch() already exists in
     * the model but had no backing column — this is that column.
     * Nullable because it's meaningless for every other role.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('assigned_batch_id')
                ->nullable()
                ->after('phone')
                ->constrained('batches')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_batch_id');
        });
    }
};
