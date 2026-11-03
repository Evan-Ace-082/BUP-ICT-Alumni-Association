<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_logs', 'target_type')) {
                $table->string('target_type')->nullable()->after('actor_id');
            }
            if (! Schema::hasColumn('audit_logs', 'details')) {
                $table->text('details')->nullable()->after('action');
            }
        });

        // Backfill existing audit log records to point to User model
        DB::table('audit_logs')
            ->whereNull('target_type')
            ->update(['target_type' => 'App\\Models\\User']);

        // In MySQL/MariaDB/PostgreSQL, drop the foreign key constraint on target_id so non-user targets (elections, etc.) can be recorded
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb', 'pgsql'])) {
            try {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->dropForeign(['target_id']);
                });
            } catch (Throwable $e) {
                // Ignore if foreign key was already dropped or doesn't exist
            }
        }
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('audit_logs', 'details')) {
                $table->dropColumn('details');
            }
            if (Schema::hasColumn('audit_logs', 'target_type')) {
                $table->dropColumn('target_type');
            }
        });
    }
};
