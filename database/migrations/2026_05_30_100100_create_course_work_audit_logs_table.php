<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_work_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('course_work_mark_id')->nullable()->constrained('course_work_marks');
            $table->string('event');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('student_enrolment_id')->constrained('student_enrolments');
            $table->foreignId('course_syllabus_module_id')->constrained('course_syllabus_modules');
            $table->foreignId('assessment_type_id')->constrained('assessment_types');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(
                ['tenant_id', 'student_enrolment_id', 'created_at'],
                'cw_audit_logs_tenant_enrol_created_idx',
            );
            $table->index(
                ['course_work_mark_id', 'created_at'],
                'cw_audit_logs_mark_created_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_work_audit_logs');
    }
};
