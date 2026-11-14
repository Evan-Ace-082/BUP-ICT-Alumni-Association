<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('student_id')->unique();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->string('department')->default('Information and Communication Technology');
            $table->string('session')->nullable(); // e.g. "2021-2022"
            $table->unsignedSmallInteger('enrollment_year')->nullable();
            $table->unsignedSmallInteger('expected_graduation_year')->nullable();
            $table->enum('status', ['active', 'graduated', 'inactive'])->default('active');
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index('verification_status');
            $table->index('status');
            $table->index('full_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
