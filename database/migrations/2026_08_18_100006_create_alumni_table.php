<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An alumni record is the official, department-verified record of a
     * graduate. It is intentionally separate from `users` (the auth
     * account). A record can exist here — e.g. imported later from an
     * Excel/Google Form sheet — long before the person ever registers
     * an account. `user_id` stays null until they claim it.
     *
     * status:
     *  - unclaimed: official record exists, no linked user account yet
     *  - active:    linked to a user account and in good standing
     *  - inactive:  record deactivated by an admin
     */
    public function up(): void
    {
        Schema::create('alumni', function (Blueprint $table) {
            $table->id();
            $table->string('student_id')->unique();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->foreignId('batch_id')->constrained('batches')->restrictOnDelete();
            $table->string('session')->nullable();
            $table->unsignedSmallInteger('graduation_year')->nullable();
            $table->string('profile_photo')->nullable();
            $table->string('phone')->nullable();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->enum('status', ['unclaimed', 'active', 'inactive'])->default('unclaimed');
            $table->timestamps();

            $table->index('status');
            $table->index('full_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni');
    }
};
