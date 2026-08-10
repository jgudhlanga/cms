<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_exam_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('candidate_number');
            $table->string('id_number')->nullable();
            $table->foreignId('institution_department_id')->nullable()->constrained('institution_departments')->nullOnDelete();
            $table->foreignId('department_level_id')->nullable()->constrained('department_levels')->nullOnDelete();
            $table->foreignId('department_course_id')->nullable()->constrained('department_courses')->nullOnDelete();
            $table->foreignId('mode_of_study_id')->nullable()->constrained('mode_of_studies')->nullOnDelete();
            $table->unsignedSmallInteger('calendar_year');
            $table->string('session');
            $table->string('comment', 32)->nullable();
            $table->string('raw_course_comment')->nullable();
            $table->boolean('comment_needs_review')->default(false);
            $table->timestamps();

            $table->unique(
                ['student_id', 'calendar_year', 'session'],
                'student_exam_results_unique'
            );
            $table->index(['tenant_id', 'candidate_number']);
            $table->index(['tenant_id', 'session']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_exam_results');
    }
};
