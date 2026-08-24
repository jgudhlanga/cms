<?php

declare(strict_types=1);

use App\Models\Applications\ApplicationCourseRequirement;
use App\Models\Applications\ApplicationLevelRequirement;
use App\Services\Applications\ApplicationRequirementBackfillService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('application_level_requirements')) {
            Schema::create('application_level_requirements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained();
                $table->unsignedBigInteger('department_level_id');
                $table->boolean('is_o_level_required');
                $table->integer('required_subjects_count')->nullable();
                $table->integer('main_subjects_count')->nullable();
                $table->json('main_subject_ids')->nullable();
                $table->integer('other_subjects_count')->nullable();
                $table->boolean('only_read_write_required');
                $table->unsignedBigInteger('required_level_id')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('department_level_id', 'alr_dept_level_fk')
                    ->references('id')
                    ->on('department_levels');

                $table->unique(
                    ['tenant_id', 'department_level_id'],
                    'alr_tenant_dept_level_unique',
                );
            });
        }

        if (! Schema::hasTable('application_course_requirements')) {
            Schema::create('application_course_requirements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained();
                $table->unsignedBigInteger('department_level_id');
                $table->unsignedBigInteger('department_course_id');
                $table->boolean('is_o_level_required');
                $table->integer('required_subjects_count')->nullable();
                $table->integer('main_subjects_count')->nullable();
                $table->json('main_subject_ids')->nullable();
                $table->integer('other_subjects_count')->nullable();
                $table->boolean('only_read_write_required');
                $table->unsignedBigInteger('required_level_id')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('department_level_id', 'acr_dept_level_fk')
                    ->references('id')
                    ->on('department_levels');
                $table->foreign('department_course_id', 'acr_dept_course_fk')
                    ->references('id')
                    ->on('department_courses');

                $table->unique(
                    ['tenant_id', 'department_level_id', 'department_course_id'],
                    'acr_tenant_level_course_unique',
                );
            });
        }

        if (
            Schema::hasTable('department_level_requirements')
            && Schema::hasTable('course_requirements')
            && ApplicationLevelRequirement::query()->count() === 0
            && ApplicationCourseRequirement::query()->count() === 0
        ) {
            $counts = app(ApplicationRequirementBackfillService::class)->backfill(
                dryRun: false,
                fresh: false,
                snapshot: true,
            );

            if ($counts['levels_skipped'] > 0 || $counts['courses_skipped'] > 0) {
                logger()->warning('Requirement backfill skipped orphan legacy rows.', [
                    'levels_skipped' => $counts['levels_skipped'],
                    'courses_skipped' => $counts['courses_skipped'],
                ]);
            }

            $expectedLevels = $counts['source_level_count'] - $counts['levels_skipped'];
            $expectedCourses = $counts['source_course_count'] - $counts['courses_skipped'];

            if ($counts['levels'] !== $expectedLevels || $counts['courses'] !== $expectedCourses) {
                throw new RuntimeException(sprintf(
                    'Requirement backfill count mismatch. Expected levels: %d, copied: %d. Expected courses: %d, copied: %d.',
                    $expectedLevels,
                    $counts['levels'],
                    $expectedCourses,
                    $counts['courses'],
                ));
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('application_course_requirements');
        Schema::dropIfExists('application_level_requirements');
    }
};
