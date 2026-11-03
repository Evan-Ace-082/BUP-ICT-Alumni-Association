<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Generic account status, not alumni-specific (Alumni already has
     * its own unrelated `status` column). Used to let a Super Admin
     * suspend an Admin's access without deleting the account or any
     * historical data. Plain string (not a DB enum), matching this
     * project's established convention (see Alumni::JOB_STATUSES).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('status')->default('active')->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
