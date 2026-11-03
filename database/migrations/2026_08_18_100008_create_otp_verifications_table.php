<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Foundation for future email OTP verification (registration and,
     * later, password reset). Keyed by email rather than a user/alumni
     * FK because an OTP may be issued before either record exists.
     * Phase 1 does not send emails or implement the verification flow.
     */
    public function up(): void
    {
        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('otp_code', 6);
            $table->string('purpose')->default('registration'); // registration, password_reset, ...
            $table->timestamp('expires_at');
            $table->boolean('is_verified')->default(false);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();

            $table->index(['email', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_verifications');
    }
};
