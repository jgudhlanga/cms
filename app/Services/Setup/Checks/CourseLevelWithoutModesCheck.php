<?php

declare(strict_types=1);

namespace App\Services\Setup\Checks;

use App\Enums\Setup\SetupGapCheckEnum;
use App\Support\Setup\SetupGapResult;
use Illuminate\Support\Facades\DB;

/**
 * Course levels that hold applications but have no mode of study set up at all.
 */
class CourseLevelWithoutModesCheck extends ProgrammeSetupCheck
{
    public function key(): SetupGapCheckEnum
    {
        return SetupGapCheckEnum::COURSE_LEVEL_WITHOUT_MODES;
    }

    /**
     * @return iterable<SetupGapResult>
     */
    public function run(): iterable
    {
        $intakePeriodId = $this->currentIntakePeriodId();

        if ($intakePeriodId === null) {
            return [];
        }

        $rows = DB::table('student_applications as sa')
            ->where('sa.intake_period_id', $intakePeriodId)
            ->whereNull('sa.deleted_at')
            ->whereNotNull('sa.institution_department_id')
            ->whereNotNull('sa.department_course_id')
            ->whereNotNull('sa.department_level_id')
            ->groupBy('sa.institution_department_id', 'sa.department_course_id', 'sa.department_level_id')
            ->select([
                'sa.institution_department_id',
                'sa.department_course_id',
                'sa.department_level_id',
                DB::raw('COUNT(*) as application_count'),
            ])
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $configured = $this->configuredModes();
        $departments = $this->departments($rows->pluck('institution_department_id')->map(fn ($id): int => (int) $id)->unique()->values()->all());
        $courses = $this->courseNames($rows->pluck('department_course_id')->map(fn ($id): int => (int) $id)->unique()->values()->all());
        $levels = $this->levelNames($rows->pluck('department_level_id')->map(fn ($id): int => (int) $id)->unique()->values()->all());

        $gaps = [];

        foreach ($rows as $row) {
            $courseId = (int) $row->department_course_id;
            $levelId = (int) $row->department_level_id;
            $departmentId = (int) $row->institution_department_id;
            $department = $departments[$departmentId] ?? null;
            $configuredModeIds = $configured->get("{$courseId}:{$levelId}");

            if ($department === null || ($configuredModeIds !== null && $configuredModeIds !== [])) {
                continue;
            }

            $courseName = $courses[$courseId] ?? (string) $courseId;
            $levelName = $levels[$levelId] ?? (string) $levelId;

            $gaps[] = new SetupGapResult(
                check: $this->key(),
                tenantId: $department['tenantId'],
                title: __('setup_gaps.course_level_without_modes_title', [
                    'course' => $courseName,
                    'level' => $levelName,
                ]),
                body: __('setup_gaps.course_level_without_modes_body', [
                    'count' => (int) $row->application_count,
                ]),
                institutionDepartmentId: $departmentId,
                url: $this->departmentUrl($departmentId),
                meta: [
                    'departmentCourseId' => $courseId,
                    'departmentLevelId' => $levelId,
                    'applicationCount' => (int) $row->application_count,
                ],
                fingerprintKey: "course:{$courseId}|level:{$levelId}",
            );
        }

        return $gaps;
    }
}
