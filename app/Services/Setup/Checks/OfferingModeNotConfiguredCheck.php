<?php

declare(strict_types=1);

namespace App\Services\Setup\Checks;

use App\Contracts\Setup\SetupGapCheck;
use App\Enums\Setup\SetupGapCheckEnum;
use App\Services\Setup\Checks\Concerns\ResolvesDepartments;
use App\Support\Setup\SetupGapResult;
use Illuminate\Support\Facades\DB;

/**
 * Online applicants can pick a mode of study the department has not actually set up for that course
 * level. This is not just drift: `ApplicationOfferingSyncService::assertValidTree` validates the level
 * and course links when the offering is saved, but never checks the chosen modes against
 * `course_level_modes` — so this can happen at write time, not only afterward.
 *
 * The blast radius is the same as an application already sitting in an unconfigured mode: the
 * applicant is accepted into something the department page cannot place on a class list.
 */
class OfferingModeNotConfiguredCheck implements SetupGapCheck
{
    use ResolvesDepartments;

    public function key(): SetupGapCheckEnum
    {
        return SetupGapCheckEnum::OFFERING_MODE_NOT_CONFIGURED;
    }

    /**
     * @return iterable<SetupGapResult>
     */
    public function run(): iterable
    {
        $rows = DB::table('application_offering_modes as aom')
            ->join('application_offering_courses as aoc', 'aoc.id', '=', 'aom.application_offering_course_id')
            ->join('application_offering_levels as aol', 'aol.id', '=', 'aoc.application_offering_level_id')
            ->join('application_offering_departments as aod', 'aod.id', '=', 'aol.application_offering_department_id')
            ->whereNull('aom.deleted_at')
            ->whereNull('aoc.deleted_at')
            ->whereNull('aol.deleted_at')
            ->whereNull('aod.deleted_at')
            ->select([
                'aom.id as offering_mode_id',
                'aom.mode_of_study_id',
                'aoc.department_course_id',
                'aol.department_level_id',
                'aod.institution_department_id',
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
            $configuredModeIds = $configured->get("{$courseId}:{$levelId}", []);

            if ($department === null || in_array($modeId, $configuredModeIds, true)) {
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
                title: __('setup_gaps.offering_mode_not_configured_title', [
                    'course' => $courseName,
                    'level' => $levelName,
                    'mode' => $modeName,
                ]),
                body: $configuredNames !== ''
                    ? __('setup_gaps.offering_mode_not_configured_body', [
                        'mode' => $modeName,
                        'course' => $courseName,
                        'level' => $levelName,
                        'configured' => $configuredNames,
                    ])
                    : __('setup_gaps.offering_mode_not_configured_body_none', [
                        'mode' => $modeName,
                        'course' => $courseName,
                        'level' => $levelName,
                    ]),
                institutionDepartmentId: $departmentId,
                url: $this->applicationOfferingUrl($departmentId),
                meta: [
                    'offeringModeId' => (int) $row->offering_mode_id,
                    'departmentCourseId' => $courseId,
                    'departmentLevelId' => $levelId,
                    'modeOfStudyId' => $modeId,
                ],
                fingerprintKey: "offering-mode:{$row->offering_mode_id}",
            );
        }

        return $gaps;
    }
}
