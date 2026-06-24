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
            if (! Schema::hasColumn('class_configs', 'academic_year_option_id')) {
                $table->foreignId('academic_year_option_id')
                    ->nullable()
                    ->after('calendar_year');
            }

            if (! Schema::hasColumn('class_configs', 'course_syllabus_ids')) {
                $table->json('course_syllabus_ids')->after('academic_year_option_id')->nullable();
            }

            if (! Schema::hasColumn('class_configs', 'status')) {
                $table->enum('status', ['open', 'closed'])
                    ->default('open')
                    ->after('students_per_class');
            }
        });

        if (Schema::hasIndex('class_configs', 'class_configs_dept_course_level_mode_year_unique')) {
            Schema::table('class_configs', function (Blueprint $table): void {
                if (! Schema::hasIndex('class_configs', 'class_configs_institution_department_id_index')) {
                    $table->index('institution_department_id');
                }
            });

            Schema::table('class_configs', function (Blueprint $table): void {
                $table->dropUnique('class_configs_dept_course_level_mode_year_unique');
            });
        }

        Schema::table('class_configs', function (Blueprint $table): void {
            if (! Schema::hasIndex('class_configs', 'class_configs_dept_course_level_mode_year_option_unique')) {
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
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_configs', function (Blueprint $table): void {
            $table->dropUnique('class_configs_dept_course_level_mode_year_option_unique');

            $table->dropColumn([
                'academic_year_option_id',
                'status',
                'course_syllabus_ids',
            ]);
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
                'class_configs_dept_course_level_mode_year_unique',
            );
        });
    }
};
