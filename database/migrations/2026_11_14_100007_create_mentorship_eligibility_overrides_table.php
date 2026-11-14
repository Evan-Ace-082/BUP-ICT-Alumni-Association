<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mentorship_eligibility_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->enum('override_type', ['auto', 'force_mentee', 'force_mentor', 'ineligible'])->default('auto');
            $table->text('reason')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('override_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentorship_eligibility_overrides');
    }
};
