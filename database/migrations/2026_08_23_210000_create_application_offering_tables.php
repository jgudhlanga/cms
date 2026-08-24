<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_offering_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->unsignedBigInteger('institution_department_id');
            $table->boolean('has_apprentice_programmes')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('institution_department_id', 'aod_inst_dept_fk')
                ->references('id')
                ->on('institution_departments');

            $table->unique(
                ['tenant_id', 'institution_department_id'],
                'aod_tenant_inst_dept_unique',
            );
        });

        Schema::create('application_offering_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->unsignedBigInteger('application_offering_department_id');
            $table->unsignedBigInteger('department_level_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('application_offering_department_id', 'aol_offering_dept_fk')
                ->references('id')
                ->on('application_offering_departments')
                ->cascadeOnDelete();
            $table->foreign('department_level_id', 'aol_dept_level_fk')
                ->references('id')
                ->on('department_levels');

            $table->unique(
                ['application_offering_department_id', 'department_level_id'],
                'aol_dept_level_unique',
            );
        });

        Schema::create('application_offering_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->unsignedBigInteger('application_offering_level_id');
            $table->unsignedBigInteger('department_course_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('application_offering_level_id', 'aoc_offering_level_fk')
                ->references('id')
                ->on('application_offering_levels')
                ->cascadeOnDelete();
            $table->foreign('department_course_id', 'aoc_dept_course_fk')
                ->references('id')
                ->on('department_courses');

            $table->unique(
                ['application_offering_level_id', 'department_course_id'],
                'aoc_level_course_unique',
            );
        });

        Schema::create('application_offering_modes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->unsignedBigInteger('application_offering_course_id');
            $table->unsignedBigInteger('mode_of_study_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('application_offering_course_id', 'aom_offering_course_fk')
                ->references('id')
                ->on('application_offering_courses')
                ->cascadeOnDelete();
            $table->foreign('mode_of_study_id', 'aom_mode_fk')
                ->references('id')
                ->on('mode_of_studies');

            $table->unique(
                ['application_offering_course_id', 'mode_of_study_id'],
                'aom_course_mode_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_offering_modes');
        Schema::dropIfExists('application_offering_courses');
        Schema::dropIfExists('application_offering_levels');
        Schema::dropIfExists('application_offering_departments');
    }
};
