<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('election_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained('elections')->cascadeOnDelete();
            $table->foreignId('election_position_id')->constrained('election_positions')->cascadeOnDelete();
            $table->foreignId('election_candidate_id')->constrained('election_candidates')->cascadeOnDelete();
            $table->foreignId('voter_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['election_id', 'election_position_id', 'voter_id'], 'unique_vote_per_position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_votes');
    }
};
