<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->upSqlite();

            return;
        }

        Schema::table('course_work_marks', function (Blueprint $table): void {
            $table->dropForeign(['student_enrolment_id']);
            $table->dropUnique('course_work_marks_unique');
            $table->index('student_enrolment_id', 'course_work_marks_student_enrolment_id_index');
            $table->foreign('student_enrolment_id')->references('id')->on('student_enrolments');
        });

        Schema::table('course_work_marks', function (Blueprint $table): void {
            $table->unsignedBigInteger('assessment_type_id')->nullable()->change();
        });

        if (! $this->assessmentTypeForeignKeyExists()) {
            Schema::table('course_work_marks', function (Blueprint $table): void {
                $table->foreign('assessment_type_id')->references('id')->on('assessment_types');
            });
        }

        DB::statement(
            'CREATE UNIQUE INDEX course_work_marks_unique ON course_work_marks (student_enrolment_id, course_syllabus_module_id, (COALESCE(assessment_type_id, 0)))'
        );
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->downSqlite();

            return;
        }

        DB::statement('DROP INDEX course_work_marks_unique ON course_work_marks');

        if ($this->assessmentTypeForeignKeyExists()) {
            Schema::table('course_work_marks', function (Blueprint $table): void {
                $table->dropForeign(['assessment_type_id']);
            });
        }

        DB::table('course_work_marks')->whereNull('assessment_type_id')->delete();

        Schema::table('course_work_marks', function (Blueprint $table): void {
            $table->unsignedBigInteger('assessment_type_id')->nullable(false)->change();
            $table->foreign('assessment_type_id')->references('id')->on('assessment_types');
        });

        Schema::table('course_work_marks', function (Blueprint $table): void {
            $table->dropForeign(['student_enrolment_id']);
            $table->dropIndex('course_work_marks_student_enrolment_id_index');
            $table->unique(
                ['student_enrolment_id', 'course_syllabus_module_id', 'assessment_type_id'],
                'course_work_marks_unique'
            );
            $table->foreign('student_enrolment_id')->references('id')->on('student_enrolments');
        });
    }

    private function upSqlite(): void
    {
        Schema::table('course_work_marks', function (Blueprint $table): void {
            $table->dropUnique('course_work_marks_unique');
        });

        Schema::table('course_work_marks', function (Blueprint $table): void {
            $table->unsignedBigInteger('assessment_type_id')->nullable()->change();
        });

        DB::statement(
            'CREATE UNIQUE INDEX course_work_marks_unique ON course_work_marks (student_enrolment_id, course_syllabus_module_id, COALESCE(assessment_type_id, 0))'
        );
    }

    private function downSqlite(): void
    {
        DB::statement('DROP INDEX course_work_marks_unique');

        DB::table('course_work_marks')->whereNull('assessment_type_id')->delete();

        Schema::table('course_work_marks', function (Blueprint $table): void {
            $table->unsignedBigInteger('assessment_type_id')->nullable(false)->change();
            $table->unique(
                ['student_enrolment_id', 'course_syllabus_module_id', 'assessment_type_id'],
                'course_work_marks_unique'
            );
        });
    }

    private function assessmentTypeForeignKeyExists(): bool
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return false;
        }

        $database = Schema::getConnection()->getDatabaseName();

        $result = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$database, 'course_work_marks', 'course_work_marks_assessment_type_id_foreign', 'FOREIGN KEY']
        );

        return $result !== null;
    }
};
