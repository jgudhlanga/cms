<?php

declare(strict_types=1);

namespace App\Services\Setup\Checks;

use App\Enums\Setup\SetupGapCheckEnum;
use App\Support\Setup\SetupGapResult;
use Illuminate\Support\Facades\DB;

/**
 * Applications sitting in a mode of study the course level is not set up for.
 *
 * This is the gap that hid 25 verified Applied Arts applications: the department page builds its mode
 * list from the configuration, so applications in an unconfigured mode have nowhere to appear.
 */
class ApplicationsInUnconfiguredModeCheck extends ProgrammeSetupCheck
{
    public function key(): SetupGapCheckEnum
    {
        return SetupGapCheckEnum::APPLICATIONS_IN_UNCONFIGURED_MODE;
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
            ->whereNotNull('sa.mode_of_study_id')
            ->groupBy('sa.institution_department_id', 'sa.department_course_id', 'sa.department_level_id', 'sa.mode_of_study_id')
            ->select([
                'sa.institution_department_id',
                'sa.department_course_id',
                'sa.department_level_id',
                'sa.mode_of_study_id',
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
        $modes = $this->modeNames();

        $gaps = [];

        foreach ($rows as $row) {
            $courseId = (int) $row->department_course_id;
            $levelId = (int) $row->department_level_id;
            $modeId = (int) $row->mode_of_study_id;
            $departmentId = (int) $row->institution_department_id;
            $department = $departments[$departmentId] ?? null;
            $configuredModeIds = $configured->get("{$courseId}:{$levelId}");

            // No configuration row at all is a different, broader gap; CourseLevelWithoutModesCheck owns it.
            if ($department === null || $configuredModeIds === null || in_array($modeId, $configuredModeIds, true)) {
                continue;
            }

            $courseName = $courses[$courseId] ?? (string) $courseId;
            $levelName = $levels[$levelId] ?? (string) $levelId;
            $modeName = $modes[$modeId] ?? (string) $modeId;
            $configuredNames = implode(', ', array_map(
                fn (int $id): string => $modes[$id] ?? (string) $id,
                $configuredModeIds,
            ));

            $gaps[] = new SetupGapResult(
                check: $this->key(),
                tenantId: $department['tenantId'],
                title: __('setup_gaps.applications_in_unconfigured_mode_title', [
                    'course' => $courseName,
                    'level' => $levelName,
                    'mode' => $modeName,
                ]),
                body: __('setup_gaps.applications_in_unconfigured_mode_body', [
                    'count' => (int) $row->application_count,
                    'mode' => $modeName,
                    'course' => $courseName,
                    'level' => $levelName,
                    'configured' => $configuredNames !== '' ? $configuredNames : '—',
                ]),
                institutionDepartmentId: $departmentId,
                url: $this->departmentUrl($departmentId),
                meta: [
                    'departmentCourseId' => $courseId,
                    'departmentLevelId' => $levelId,
                    'modeOfStudyId' => $modeId,
                    'applicationCount' => (int) $row->application_count,
                    'configuredModeIds' => $configuredModeIds,
                ],
                fingerprintKey: "course:{$courseId}|level:{$levelId}|mode:{$modeId}",
            );
        }

        return $gaps;
    }
}
