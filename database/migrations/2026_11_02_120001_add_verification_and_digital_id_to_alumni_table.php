<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('alumni', function (Blueprint $table) {
            $table->string('alumni_id')->nullable()->unique()->after('id');
            $table->string('verification_status')->default('pending')->after('status');
            $table->string('qr_token', 64)->nullable()->unique()->after('verification_status');
            $table->foreignId('verified_by')->nullable()->after('qr_token')->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
            $table->foreignId('rejected_by')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            $table->text('rejection_reason')->nullable()->after('rejected_at');

            $table->index('verification_status');
        });

        // Ensure existing approved/active alumni remain verified and do not accidentally become pending
        \Illuminate\Support\Facades\DB::table('alumni')
            ->whereNotNull('user_id')
            ->whereIn('status', ['active', 'approved'])
            ->update([
                'verification_status' => 'verified',
                'verified_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alumni', function (Blueprint $table) {
            $table->dropIndex(['verification_status']);
            $table->dropForeign(['verified_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropColumn([
                'alumni_id',
                'verification_status',
                'qr_token',
                'verified_by',
                'verified_at',
                'rejected_by',
                'rejected_at',
                'rejection_reason',
            ]);
        });
    }
};
