<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Profile fields. current_institution/designation/higher_studies may
     * originate from the master Excel import (a later phase); company,
     * address, linkedin_url, facebook_url, and blood_group are always
     * self-reported. All are editable by the alumni after registration
     * (see PROJECT_MASTER_SPECIFICATION.md, Phase 2 profile-editing
     * rules) — none of them are official identity fields.
     */
    public function up(): void
    {
        Schema::table('alumni', function (Blueprint $table) {
            $table->string('current_institution')->nullable()->after('graduation_year');
            $table->string('designation')->nullable()->after('current_institution');
            $table->string('company')->nullable()->after('designation');
            $table->string('higher_studies')->nullable()->after('company');
            $table->text('address')->nullable()->after('phone');
            $table->string('linkedin_url')->nullable()->after('address');
            $table->string('facebook_url')->nullable()->after('linkedin_url');
            $table->string('blood_group', 5)->nullable()->after('facebook_url');
        });
    }

    public function down(): void
    {
        Schema::table('alumni', function (Blueprint $table) {
            $table->dropColumn([
                'current_institution', 'designation', 'company', 'higher_studies',
                'address', 'linkedin_url', 'facebook_url', 'blood_group',
            ]);
        });
    }
};
