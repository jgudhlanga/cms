<?php

use App\Enums\Institution\ModeOfStudyEnum;
use App\Models\Institution\CourseLevelMode;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\ModeOfStudy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institution_departments', function (Blueprint $table) {
            $table->boolean('has_apprentice_courses')
                ->default(false)
                ->after('division_id');
        });

        $blockReleaseModeId = ModeOfStudy::query()
            ->where('name', ModeOfStudyEnum::BLOCK_RELEASE->value)
            ->value('id');

        if ($blockReleaseModeId === null) {
            return;
        }

        $eligibleDepartmentIds = CourseLevelMode::query()
            ->get()
            ->filter(fn (CourseLevelMode $clm) => in_array(
                (int) $blockReleaseModeId,
                array_map('intval', $clm->modes ?? []),
                true,
            ))
            ->pluck('department_course_id')
            ->unique();

        if ($eligibleDepartmentIds->isEmpty()) {
            return;
        }

        $institutionDepartmentIds = DepartmentCourse::query()
            ->whereIn('id', $eligibleDepartmentIds)
            ->where('show_on_current_application_period', true)
            ->pluck('institution_department_id')
            ->unique();

        if ($institutionDepartmentIds->isEmpty()) {
            return;
        }

        InstitutionDepartment::query()
            ->whereIn('id', $institutionDepartmentIds)
            ->whereHas('departmentLevels', fn ($q) => $q->where('show_on_current_application_period', true))
            ->update(['has_apprentice_courses' => true]);
    }

    public function down(): void
    {
        Schema::table('institution_departments', function (Blueprint $table) {
            $table->dropColumn('has_apprentice_courses');
        });
    }
};
