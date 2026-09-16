<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programme_stages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('department_level_course_id')
                ->constrained('department_level_courses', 'id', 'prog_stage_dlc_fk')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->unsignedTinyInteger('stage_number');
            $table->string('code', 64);
            $table->string('name');
            $table->string('kind', 32)->default('taught');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['department_level_course_id', 'stage_number'], 'prog_stage_dlc_number_unq');
        });

        Schema::table('programme_semesters', function (Blueprint $table): void {
            $table->foreignId('programme_stage_id')
                ->nullable()
                ->after('department_level_course_id')
                ->constrained('programme_stages', 'id', 'prog_sem_stage_fk')
                ->nullOnDelete();
            $table->unsignedTinyInteger('year_number')->nullable()->after('position');
            $table->unsignedTinyInteger('period_in_year')->nullable()->after('year_number');
        });

        Schema::create('student_programme_stages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('student_application_id')
                ->nullable()
                ->constrained('student_applications')
                ->nullOnDelete();
            $table->foreignId('department_level_course_id')
                ->constrained('department_level_courses', 'id', 'stud_prog_stage_dlc_fk')
                ->cascadeOnDelete();
            $table->foreignId('programme_stage_id')
                ->constrained('programme_stages', 'id', 'stud_prog_stage_fk')
                ->cascadeOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'programme_stage_id'], 'stud_prog_stage_unique');
        });

        Schema::table('student_applications', function (Blueprint $table): void {
            $table->foreignId('programme_stage_id')
                ->nullable()
                ->after('department_course_id')
                ->constrained('programme_stages', 'id', 'stud_app_prog_stage_fk')
                ->nullOnDelete();
        });

        Schema::table('student_enrolments', function (Blueprint $table): void {
            $table->foreignId('programme_stage_id')
                ->nullable()
                ->after('department_course_id')
                ->constrained('programme_stages', 'id', 'stud_enrol_prog_stage_fk')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_enrolments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('programme_stage_id');
        });

        Schema::table('student_applications', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('programme_stage_id');
        });

        Schema::dropIfExists('student_programme_stages');

        Schema::table('programme_semesters', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('programme_stage_id');
            $table->dropColumn(['year_number', 'period_in_year']);
        });

        Schema::dropIfExists('programme_stages');
    }
};
