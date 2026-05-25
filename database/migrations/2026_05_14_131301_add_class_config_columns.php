<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('class_configs', function (Blueprint $table) {

            $table->foreignId('academic_year_option_id')
                ->nullable()
                ->after('calendar_year');
            $table->json('course_syllabus_ids')->after('academic_year_option_id')->nullable();
            $table->enum('status', ['open', 'closed'])
                ->default('open')
                ->after('students_per_class');
            // Add new unique index
            $table->unique(
                [
                    'institution_department_id',
                    'department_course_id',
                    'department_level_id',
                    'mode_of_study_id',
                    'calendar_year',
                    'academic_year_option_id',
                ],
                'class_configs_dept_course_level_mode_year_option_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_configs', function (Blueprint $table) {

            // Drop new unique index
            $table->dropUnique(
                'class_configs_dept_course_level_mode_year_option_unique'
            );

            $table->dropColumn([
                'academic_year_option_id',
                'status',
                'course_syllabus_ids',
            ]);
        });
    }
};
