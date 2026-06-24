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
        if (! Schema::hasIndex('class_configs', 'class_configs_dept_course_level_mode_year_unique')) {
            return;
        }

        Schema::table('class_configs', function (Blueprint $table): void {
            if (! Schema::hasIndex('class_configs', 'class_configs_institution_department_id_index')) {
                $table->index('institution_department_id');
            }
        });

        Schema::table('class_configs', function (Blueprint $table): void {
            $table->dropUnique('class_configs_dept_course_level_mode_year_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasIndex('class_configs', 'class_configs_dept_course_level_mode_year_unique')) {
            return;
        }

        Schema::table('class_configs', function (Blueprint $table): void {
            $table->unique(
                [
                    'institution_department_id',
                    'department_course_id',
                    'department_level_id',
                    'mode_of_study_id',
                    'calendar_year',
                ],
                'class_configs_dept_course_level_mode_year_unique',
            );
        });
    }
};
