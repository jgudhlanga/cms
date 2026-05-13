<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_configs', function (Blueprint $table): void {
            $table->dropUnique('class_configs_dept_course_level_mode_year_unique');
        });

        Schema::table('class_configs', function (Blueprint $table): void {
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

    public function down(): void
    {
        Schema::table('class_configs', function (Blueprint $table): void {
            $table->dropUnique('class_configs_dept_course_level_mode_year_option_unique');
        });

        Schema::table('class_configs', function (Blueprint $table): void {
            $table->unique(
                [
                    'institution_department_id',
                    'department_course_id',
                    'department_level_id',
                    'mode_of_study_id',
                    'calendar_year',
                ],
                'class_configs_dept_course_level_mode_year_unique'
            );
        });
    }
};
