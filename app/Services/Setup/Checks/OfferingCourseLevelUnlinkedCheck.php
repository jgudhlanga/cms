<?php

declare(strict_types=1);

namespace App\Services\Setup\Checks;

use App\Contracts\Setup\SetupGapCheck;
use App\Enums\Setup\SetupGapCheckEnum;
use App\Services\Setup\Checks\Concerns\ResolvesDepartments;
use App\Support\Setup\SetupGapResult;
use Illuminate\Support\Facades\DB;

/**
 * An offering exposes a course at a level that was linked when the offering was saved
 * (`ApplicationOfferingSyncService::assertValidTree` checks this at write time) but department setup has
 * since unlinked — the course-level pairing no longer exists in `department_level_courses`, even though
 * the department course and department level records themselves are still there.
 *
 * A department_course or department_level that has been deleted entirely is a different, more severe
 * problem — see OfferingReferencesDeletedRecordCheck — so this check only fires while both parents are
 * still live.
 */
class OfferingCourseLevelUnlinkedCheck implements SetupGapCheck
{
    use ResolvesDepartments;

    public function key(): SetupGapCheckEnum
    {
        return SetupGapCheckEnum::OFFERING_COURSE_LEVEL_UNLINKED;
    }

    /**
     * @return iterable<SetupGapResult>
     */
    public function run(): iterable
    {
        $rows = DB::table('application_offering_courses as aoc')
            ->join('application_offering_levels as aol', 'aol.id', '=', 'aoc.application_offering_level_id')
            ->join('application_offering_departments as aod', 'aod.id', '=', 'aol.application_offering_department_id')
            ->join('department_courses as dc', 'dc.id', '=', 'aoc.department_course_id')
            ->join('department_levels as dl', 'dl.id', '=', 'aol.department_level_id')
            ->whereNull('aoc.deleted_at')
            ->whereNull('aol.deleted_at')
            ->whereNull('aod.deleted_at')
            // Both parents must still exist for this to be "unlinked" rather than "deleted".
            ->whereNull('dc.deleted_at')
            ->whereNull('dl.deleted_at')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('department_level_courses as dlc')
                    ->whereColumn('dlc.department_course_id', 'aoc.department_course_id')
                    ->whereColumn('dlc.department_level_id', 'aol.department_level_id');
            })
            ->select([
                'aoc.id as offering_course_id',
                'aoc.department_course_id',
                'aol.department_level_id',
                'aod.institution_department_id',
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
                title: __('setup_gaps.offering_course_level_unlinked_title', [
                    'course' => $courseName,
                    'level' => $levelName,
                ]),
                body: __('setup_gaps.offering_course_level_unlinked_body', [
                    'course' => $courseName,
                    'level' => $levelName,
                ]),
                institutionDepartmentId: $departmentId,
                url: $this->applicationOfferingUrl($departmentId),
                meta: [
                    'offeringCourseId' => (int) $row->offering_course_id,
                    'departmentCourseId' => $courseId,
                    'departmentLevelId' => $levelId,
                ],
                fingerprintKey: "offering-course:{$row->offering_course_id}",
            );
        }

        return $gaps;
    }
}
