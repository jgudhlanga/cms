<?php

declare(strict_types=1);

namespace App\Services\Setup\Checks;

use App\Enums\Setup\SetupGapCheckEnum;
use App\Support\Setup\SetupGapResult;
use Illuminate\Support\Facades\DB;

/**
 * Mode rows left behind on a level that is no longer linked to the course.
 *
 * These are the leftovers `maintenance:repair-orphan-course-level-modes` reports; raising them as gaps
 * means nobody has to remember to run that command to find out.
 */
class ModesOnUnlinkedLevelCheck extends ProgrammeSetupCheck
{
    public function key(): SetupGapCheckEnum
    {
        return SetupGapCheckEnum::MODES_ON_UNLINKED_LEVEL;
    }

    /**
     * @return iterable<SetupGapResult>
     */
    public function run(): iterable
    {
        $rows = DB::table('course_level_modes as clm')
            ->join('department_courses as dc', 'dc.id', '=', 'clm.department_course_id')
            ->whereNull('clm.deleted_at')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('department_level_courses as dlc')
                    ->whereColumn('dlc.department_course_id', 'clm.department_course_id')
                    ->whereColumn('dlc.department_level_id', 'clm.department_level_id');
            })
            ->select([
                'clm.department_course_id',
                'clm.department_level_id',
                'dc.institution_department_id',
            ])
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $departments = $this->departments($rows->pluck('institution_department_id')->map(fn ($id): int => (int) $id)->unique()->values()->all());
        $courses = $this->courseNames($rows->pluck('department_course_id')->map(fn ($id): int => (int) $id)->unique()->values()->all());
        $levels = $this->levelNames($rows->pluck('department_level_id')->map(fn ($id): int => (int) $id)->unique()->values()->all());

        $gaps = [];

        foreach ($rows as $row) {
            $courseId = (int) $row->department_course_id;
            $levelId = (int) $row->department_level_id;
            $departmentId = (int) $row->institution_department_id;
            $department = $departments[$departmentId] ?? null;

            if ($department === null) {
                continue;
            }

            $courseName = $courses[$courseId] ?? (string) $courseId;
            $levelName = $levels[$levelId] ?? (string) $levelId;

            $gaps[] = new SetupGapResult(
                check: $this->key(),
                tenantId: $department['tenantId'],
                title: __('setup_gaps.modes_on_unlinked_level_title', [
                    'course' => $courseName,
                    'level' => $levelName,
                ]),
                body: __('setup_gaps.modes_on_unlinked_level', [
                    'course' => $courseName,
                    'level' => $levelName,
                ]),
                institutionDepartmentId: $departmentId,
                url: $this->departmentUrl($departmentId),
                meta: [
                    'departmentCourseId' => $courseId,
                    'departmentLevelId' => $levelId,
                ],
                fingerprintKey: "course:{$courseId}|level:{$levelId}",
            );
        }

        return $gaps;
    }
}
