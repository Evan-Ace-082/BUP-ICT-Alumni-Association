<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Batches are data, not code. The department currently runs up to
     * Batch 12 and the association currently covers Batch 01-08, but
     * nothing about that is encoded here — new batches are simply new
     * rows, added later through the admin UI (a future phase).
     */
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_name');           // e.g. "Batch 01"
            $table->string('batch_code')->unique();  // e.g. "B01"
            $table->string('session')->nullable();    // e.g. "2011-2012"
            $table->unsignedSmallInteger('graduation_year')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
