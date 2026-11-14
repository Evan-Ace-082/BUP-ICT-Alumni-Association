<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mentor_profile_category', function (Blueprint $table) {
            $table->foreignId('mentor_profile_id')->constrained('mentor_profiles')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('mentorship_categories')->cascadeOnDelete();
            $table->primary(['mentor_profile_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentor_profile_category');
    }
};
