<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('academic_year_options', 'semesters');

        $this->renameForeignIdColumn(
            table: 'student_enrolments',
            from: 'academic_year_option_id',
            to: 'semester_id',
            referencedTable: 'semesters',
            nullable: false,
        );

        if (Schema::hasIndex('class_configs', 'class_configs_dept_course_level_mode_year_option_unique')) {
            Schema::table('class_configs', function (Blueprint $table): void {
                $table->dropUnique('class_configs_dept_course_level_mode_year_option_unique');
            });
        }

        $this->renameForeignIdColumn(
            table: 'class_configs',
            from: 'academic_year_option_id',
            to: 'semester_id',
            referencedTable: 'semesters',
            nullable: true,
            hasForeignKey: false,
        );

        Schema::table('class_configs', function (Blueprint $table): void {
            $table->unique(
                [
                    'institution_department_id',
                    'department_course_id',
                    'department_level_id',
                    'mode_of_study_id',
                    'calendar_year',
                    'semester_id',
                ],
                'class_configs_dept_course_level_mode_year_option_unique'
            );
        });

        $this->renameForeignIdColumn(
            table: 'course_syllabus_modules',
            from: 'academic_year_option_id',
            to: 'semester_id',
            referencedTable: 'semesters',
            nullable: true,
        );

        $this->renamePermissionSuffix(':academic-year-options', ':semesters');
    }

    public function down(): void
    {
        $this->renamePermissionSuffix(':semesters', ':academic-year-options');

        $this->renameForeignIdColumn(
            table: 'course_syllabus_modules',
            from: 'semester_id',
            to: 'academic_year_option_id',
            referencedTable: 'academic_year_options',
            nullable: true,
        );

        if (Schema::hasIndex('class_configs', 'class_configs_dept_course_level_mode_year_option_unique')) {
            Schema::table('class_configs', function (Blueprint $table): void {
                $table->dropUnique('class_configs_dept_course_level_mode_year_option_unique');
            });
        }

        $this->renameForeignIdColumn(
            table: 'class_configs',
            from: 'semester_id',
            to: 'academic_year_option_id',
            referencedTable: 'academic_year_options',
            nullable: true,
            hasForeignKey: false,
        );

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

        $this->renameForeignIdColumn(
            table: 'student_enrolments',
            from: 'semester_id',
            to: 'academic_year_option_id',
            referencedTable: 'academic_year_options',
            nullable: false,
        );

        Schema::rename('semesters', 'academic_year_options');
    }

    private function renameForeignIdColumn(
        string $table,
        string $from,
        string $to,
        string $referencedTable,
        bool $nullable,
        bool $hasForeignKey = true,
    ): void {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $from)) {
            return;
        }

        if ($hasForeignKey) {
            Schema::table($table, function (Blueprint $blueprint) use ($from): void {
                $blueprint->dropForeign([$from]);
            });
        }

        Schema::table($table, function (Blueprint $blueprint) use ($from, $to): void {
            $blueprint->renameColumn($from, $to);
        });

        Schema::table($table, function (Blueprint $blueprint) use ($to, $referencedTable): void {
            $blueprint->foreign($to)->references('id')->on($referencedTable);
        });
    }

    private function renamePermissionSuffix(string $from, string $to): void
    {
        DB::table('permissions')
            ->where('name', 'like', '%'.$from)
            ->orderBy('id')
            ->get(['id', 'name'])
            ->each(function (object $permission) use ($from, $to): void {
                DB::table('permissions')
                    ->where('id', $permission->id)
                    ->update([
                        'name' => str_replace($from, $to, $permission->name),
                    ]);
            });
    }
};
