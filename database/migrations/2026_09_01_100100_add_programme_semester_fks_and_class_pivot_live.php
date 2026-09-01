<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_semesters', function (Blueprint $table): void {
            $table->foreignId('programme_semester_id')
                ->nullable()
                ->after('semester_id')
                ->constrained('programme_semesters', 'id', 'stu_sem_prog_sem_fk')
                ->nullOnDelete();
        });

        Schema::table('class_configs', function (Blueprint $table): void {
            $table->foreignId('programme_semester_id')
                ->nullable()
                ->after('semester_id')
                ->constrained('programme_semesters', 'id', 'class_cfg_prog_sem_fk')
                ->nullOnDelete();
            $table->string('name')->nullable()->after('programme_semester_id');
            $table->string('kind', 32)->default('standard')->after('name');
            $table->string('slug', 64)->default('standard')->after('kind');
        });

        Schema::table('course_syllabus_modules', function (Blueprint $table): void {
            $table->foreignId('programme_semester_id')
                ->nullable()
                ->after('semester_id')
                ->constrained('programme_semesters', 'id', 'csm_prog_sem_fk')
                ->nullOnDelete();
        });

        Schema::table('academic_calendar_student_enrolments', function (Blueprint $table): void {
            $table->boolean('is_live')->default(true)->after('academic_calendar_class_id');
            $table->timestamp('concluded_at')->nullable()->after('is_live');
        });
    }

    public function down(): void
    {
        Schema::table('academic_calendar_student_enrolments', function (Blueprint $table): void {
            $table->dropColumn(['is_live', 'concluded_at']);
        });

        Schema::table('course_syllabus_modules', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('programme_semester_id');
        });

        Schema::table('class_configs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('programme_semester_id');
            $table->dropColumn(['name', 'kind', 'slug']);
        });

        Schema::table('student_semesters', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('programme_semester_id');
        });
    }
};
