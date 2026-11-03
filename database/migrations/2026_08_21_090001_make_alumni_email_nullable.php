<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master email is "if available" data from the official Excel
     * import — not every imported record is guaranteed to have one.
     * This only relaxes the NOT NULL constraint; the column's type,
     * length, and existing unique index are untouched. MySQL and
     * SQLite both permit multiple NULLs under a unique index, so no
     * index changes are needed here.
     *
     * Laravel 11+ (including 12) modifies standard column types like
     * `string` natively — no doctrine/dbal dependency required.
     */
    public function up(): void
    {
        Schema::table('alumni', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('alumni', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};
