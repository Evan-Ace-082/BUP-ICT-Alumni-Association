<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievement_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('approved'); // approved, pending, rejected
            $table->boolean('is_active')->default(true);
            $table->foreignId('suggested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('achievements', function (Blueprint $table) {
            $table->foreignId('achievement_category_id')
                ->nullable()
                ->after('description')
                ->constrained('achievement_categories')
                ->nullOnDelete();
        });

        // Seed initial default categories
        $now = Carbon::now();
        $defaults = [
            'Banking & Finance',
            'Research & Academia',
            'BCS & Government',
            'Teaching & Education',
            'Corporate & Industry',
            'International & Higher Studies',
            'Entrepreneurship',
            'Others',
        ];

        foreach ($defaults as $name) {
            DB::table('achievement_categories')->insertOrIgnore([
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => "Official achievement category for {$name}.",
                'status' => 'approved',
                'is_active' => true,
                'suggested_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->dropForeign(['achievement_category_id']);
            $table->dropColumn('achievement_category_id');
        });

        Schema::dropIfExists('achievement_categories');
    }
};
