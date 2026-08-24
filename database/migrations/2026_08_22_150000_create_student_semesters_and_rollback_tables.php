<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_semesters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_enrolment_id')
                ->constrained('student_enrolments', 'id', 'stu_sem_enrolment_fk')
                ->cascadeOnDelete();
            $table->foreignId('semester_id')
                ->constrained('semesters', 'id', 'stu_sem_semester_fk');
            $table->foreignId('student_enrolment_status_id')
                ->constrained('student_enrolment_statuses', 'id', 'stu_sem_status_fk');
            $table->json('course_syllabus_ids')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['student_enrolment_id', 'semester_id'], 'stu_sem_enrolment_semester_unq');
        });

        Schema::table('academic_calendar_student_enrolments', function (Blueprint $table): void {
            $table->foreignId('student_semesters_id')
                ->nullable()
                ->after('student_enrolment_id')
                ->constrained('student_semesters', 'id', 'acc_cal_stu_enr_stu_sem_fk')
                ->nullOnDelete();
            $table->unique(
                ['academic_calendar_class_id', 'student_semesters_id'],
                'acc_cal_stu_enr_class_stu_sem_unq',
            );
        });

        Schema::create('student_semester_rollback_enrolments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('enrolment_id');
            $table->unsignedBigInteger('semester_id')->nullable();
            $table->unsignedBigInteger('student_enrolment_status_id')->nullable();
            $table->unsignedBigInteger('academic_calendar_id')->nullable();
            $table->json('course_syllabus_ids')->nullable();
            $table->unsignedBigInteger('collapsed_into_id')->nullable();
            $table->boolean('was_soft_deleted')->default(false);
            $table->timestamps();
            $table->unique('enrolment_id', 'stu_sem_rollback_enrolment_unq');
        });

        Schema::create('student_semester_rollback_class_pivots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('academic_calendar_student_enrolment_id');
            $table->unsignedBigInteger('original_student_enrolment_id');
            $table->unsignedBigInteger('student_semesters_id')->nullable();
            $table->timestamps();
            $table->unique('academic_calendar_student_enrolment_id', 'stu_sem_rollback_pivot_unq');
        });
    }

    public function down(): void
    {
        Schema::table('academic_calendar_student_enrolments', function (Blueprint $table): void {
            $table->dropUnique('acc_cal_stu_enr_class_stu_sem_unq');
            $table->dropConstrainedForeignId('student_semesters_id');
        });

        Schema::dropIfExists('student_semester_rollback_class_pivots');
        Schema::dropIfExists('student_semester_rollback_enrolments');
        Schema::dropIfExists('student_semesters');
    }
};
