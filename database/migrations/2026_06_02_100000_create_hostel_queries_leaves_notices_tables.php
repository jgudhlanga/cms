<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hostel_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('student_id')->constrained();
            $table->string('category');
            $table->string('subject');
            $table->text('description');
            $table->string('priority')->default('medium');
            $table->string('status')->default('open');
            $table->text('resolution_notes')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hostel_leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('student_id')->constrained();
            $table->string('leave_type');
            $table->date('from_date');
            $table->date('to_date');
            $table->text('reason')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hostel_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('posted_by')->constrained('users');
            $table->string('title');
            $table->text('content');
            $table->string('type')->default('general');
            $table->string('status')->default('pending');
            $table->boolean('is_urgent')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hostel_notice_hostel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hostel_notice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hostel_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['hostel_notice_id', 'hostel_id']);
        });

        Schema::create('hostel_notice_floors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hostel_notice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hostel_id')->constrained()->cascadeOnDelete();
            $table->integer('floor_number');
            $table->timestamps();

            $table->unique(['hostel_notice_id', 'hostel_id', 'floor_number'], 'hostel_notice_floor_unique');
        });

        Schema::create('hostel_notice_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hostel_notice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['hostel_notice_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hostel_notice_student');
        Schema::dropIfExists('hostel_notice_floors');
        Schema::dropIfExists('hostel_notice_hostel');
        Schema::dropIfExists('hostel_notices');
        Schema::dropIfExists('hostel_leaves');
        Schema::dropIfExists('hostel_queries');
    }
};
