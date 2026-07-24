<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_enrolments', function (Blueprint $table): void {
            $table->index(
                ['academic_calendar_id', 'deleted_at'],
                'student_enrolments_calendar_deleted_index'
            );
            $table->index(
                ['academic_calendar_id', 'mode_of_study_id'],
                'student_enrolments_calendar_mode_index'
            );
            $table->index(
                ['student_id', 'academic_calendar_id'],
                'student_enrolments_student_calendar_index'
            );
        });

        Schema::table('student_applications', function (Blueprint $table): void {
            $table->index(
                [
                    'intake_period_id',
                    'institution_department_id',
                    'department_level_id',
                    'mode_of_study_id',
                    'department_course_id',
                ],
                'student_applications_class_list_filter_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('student_enrolments', function (Blueprint $table): void {
            $table->dropIndex('student_enrolments_calendar_deleted_index');
            $table->dropIndex('student_enrolments_calendar_mode_index');
            $table->dropIndex('student_enrolments_student_calendar_index');
        });

        Schema::table('student_applications', function (Blueprint $table): void {
            $table->dropIndex('student_applications_class_list_filter_index');
        });
    }
};
