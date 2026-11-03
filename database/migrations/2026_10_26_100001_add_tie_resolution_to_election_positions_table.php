<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('election_positions', function (Blueprint $table) {
            $table->timestamp('tie_resolved_at')->nullable();
            $table->string('tie_resolution_method')->nullable();
            $table->text('tie_resolution_reason')->nullable();
            $table->foreignId('tie_resolved_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('election_positions', function (Blueprint $table) {
            $table->dropForeign(['tie_resolved_by']);
            $table->dropColumn([
                'tie_resolved_at',
                'tie_resolution_method',
                'tie_resolution_reason',
                'tie_resolved_by',
            ]);
        });
    }
};
