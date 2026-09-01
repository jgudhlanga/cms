<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_module_exemptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_application_id')
                ->constrained('student_applications', 'id', 'stu_mod_exempt_app_fk')
                ->cascadeOnDelete();
            $table->foreignId('course_syllabus_module_id')
                ->constrained('course_syllabus_modules', 'id', 'stu_mod_exempt_mod_fk')
                ->cascadeOnDelete();
            $table->string('source', 32)->default('hexco_znqf');
            $table->string('evidence_reference')->nullable();
            $table->foreignId('granted_by')->nullable()->constrained('users', 'id', 'stu_mod_exempt_user_fk')->nullOnDelete();
            $table->timestamp('granted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(
                ['student_application_id', 'course_syllabus_module_id'],
                'stu_mod_exempt_app_module_unq',
            );
        });

        Schema::create('programme_structure_rollback_dlcs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('department_level_course_id')->unique('prog_rb_dlc_unq');
            $table->unsignedTinyInteger('duration_years')->nullable();
            $table->unsignedTinyInteger('taught_semester_count')->nullable();
            $table->boolean('includes_industrial_attachment')->nullable();
            $table->unsignedTinyInteger('attachment_semester_count')->nullable();
            $table->timestamps();
        });

        Schema::create('programme_semester_rollback_inclusions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('student_semester_id')->unique('prog_rb_incl_unq');
            $table->unsignedBigInteger('semester_id')->nullable();
            $table->unsignedBigInteger('programme_semester_id')->nullable();
            $table->unsignedBigInteger('student_enrolment_status_id')->nullable();
            $table->json('course_syllabus_ids')->nullable();
            $table->boolean('was_created_by_backfill')->default(false);
            $table->timestamps();
        });

        Schema::create('programme_semester_rollback_class_configs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('class_config_id')->unique('prog_rb_cfg_unq');
            $table->unsignedBigInteger('semester_id')->nullable();
            $table->unsignedBigInteger('programme_semester_id')->nullable();
            $table->string('name')->nullable();
            $table->string('kind', 32)->nullable();
            $table->string('slug', 64)->nullable();
            $table->boolean('was_created_by_backfill')->default(false);
            $table->timestamps();
        });

        Schema::create('programme_semester_rollback_modules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('course_syllabus_module_id')->unique('prog_rb_mod_unq');
            $table->unsignedBigInteger('semester_id')->nullable();
            $table->unsignedBigInteger('programme_semester_id')->nullable();
            $table->timestamps();
        });

        Schema::create('programme_semester_rollback_class_pivots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('academic_calendar_student_enrolment_id')->unique('prog_rb_pivot_unq');
            $table->unsignedBigInteger('academic_calendar_class_id')->nullable();
            $table->unsignedBigInteger('student_semesters_id')->nullable();
            $table->boolean('is_live')->nullable();
            $table->timestamp('concluded_at')->nullable();
            $table->boolean('was_created_by_backfill')->default(false);
            $table->timestamps();
        });

        Schema::create('programme_semester_progression_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants', 'id', 'prog_prog_run_tenant_fk')->nullOnDelete();
            $table->foreignId('academic_calendar_class_id')->nullable()->constrained('academic_calendar_classes', 'id', 'prog_prog_run_class_fk')->nullOnDelete();
            $table->foreignId('triggered_by')->nullable()->constrained('users', 'id', 'prog_prog_run_user_fk')->nullOnDelete();
            $table->string('action', 64)->default('continue_and_reseat');
            $table->unsignedInteger('affected_count')->default(0);
            $table->boolean('dry_run')->default(false);
            $table->timestamps();
        });

        Schema::create('programme_semester_progression_run_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('programme_semester_progression_run_id')
                ->constrained('programme_semester_progression_runs', 'id', 'prog_prog_item_run_fk')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('student_enrolment_id');
            $table->unsignedBigInteger('previous_student_semester_id')->nullable();
            $table->unsignedBigInteger('new_student_semester_id')->nullable();
            $table->unsignedBigInteger('previous_pivot_id')->nullable();
            $table->unsignedBigInteger('new_pivot_id')->nullable();
            $table->unsignedBigInteger('previous_programme_semester_id')->nullable();
            $table->unsignedBigInteger('new_programme_semester_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programme_semester_progression_run_items');
        Schema::dropIfExists('programme_semester_progression_runs');
        Schema::dropIfExists('programme_semester_rollback_class_pivots');
        Schema::dropIfExists('programme_semester_rollback_modules');
        Schema::dropIfExists('programme_semester_rollback_class_configs');
        Schema::dropIfExists('programme_semester_rollback_inclusions');
        Schema::dropIfExists('programme_structure_rollback_dlcs');
        Schema::dropIfExists('student_module_exemptions');
    }
};
