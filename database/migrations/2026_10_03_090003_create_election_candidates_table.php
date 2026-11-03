<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('election_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained('elections')->cascadeOnDelete();
            $table->foreignId('election_position_id')->constrained('election_positions')->cascadeOnDelete();
            $table->foreignId('alumni_id')->constrained('alumni')->cascadeOnDelete();
            $table->text('statement')->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('is_winner')->default(false);
            $table->timestamps();

            $table->unique(['election_id', 'alumni_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_candidates');
    }
};
