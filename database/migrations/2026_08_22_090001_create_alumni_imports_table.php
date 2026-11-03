<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Simple import history record. One row per dry-run or confirmed
     * import attempt. error_report holds a JSON array of
     * {row, student_id, reason} entries for failed/skipped rows.
     */
    public function up(): void
    {
        Schema::create('alumni_imports', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('successful_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['dry_run', 'completed', 'failed', 'rolled_back'])->default('dry_run');
            $table->timestamp('imported_at')->nullable();
            $table->json('error_report')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni_imports');
    }
};
