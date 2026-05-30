<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_work_marks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('student_enrolment_id')->constrained('student_enrolments');
            $table->foreignId('course_syllabus_module_id')->constrained('course_syllabus_modules');
            $table->foreignId('assessment_type_id')->constrained('assessment_types');
            $table->unsignedInteger('mark')->nullable();
            $table->text('remark')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['student_enrolment_id', 'course_syllabus_module_id', 'assessment_type_id'],
                'course_work_marks_unique'
            );
            $table->index(['tenant_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_work_marks');
    }
};
