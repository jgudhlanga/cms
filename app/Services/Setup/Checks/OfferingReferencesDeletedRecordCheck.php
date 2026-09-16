<?php

declare(strict_types=1);

namespace App\Services\Setup\Checks;

use App\Contracts\Setup\SetupGapCheck;
use App\Enums\Setup\SetupGapCheckEnum;
use App\Services\Setup\Checks\Concerns\ResolvesDepartments;
use App\Support\Setup\SetupGapResult;
use Illuminate\Support\Facades\DB;

/**
 * An offering level or offering course still points at a department_level or department_course that has
 * since been soft-deleted. The foreign key does not stop this: soft deletes leave the row (and the id)
 * in place, so the offering keeps referencing it silently, exposing a course or level to applicants that
 * the department no longer has.
 */
class OfferingReferencesDeletedRecordCheck implements SetupGapCheck
{
    use ResolvesDepartments;

    public function key(): SetupGapCheckEnum
    {
        return SetupGapCheckEnum::OFFERING_REFERENCES_DELETED_RECORD;
    }

    /**
     * @return iterable<SetupGapResult>
     */
    public function run(): iterable
    {
        $gaps = [
            ...$this->offeringLevelsOnDeletedLevels(),
            ...$this->offeringCoursesOnDeletedCourses(),
        ];

        return $gaps;
    }

    /**
     * @return list<SetupGapResult>
     */
    private function offeringLevelsOnDeletedLevels(): array
    {
        $rows = DB::table('application_offering_levels as aol')
            ->join('application_offering_departments as aod', 'aod.id', '=', 'aol.application_offering_department_id')
            ->join('department_levels as dl', 'dl.id', '=', 'aol.department_level_id')
            ->whereNull('aol.deleted_at')
            ->whereNull('aod.deleted_at')
            ->whereNotNull('dl.deleted_at')
            ->select(['aol.id as offering_level_id', 'aol.department_level_id', 'aod.institution_department_id'])
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $departments = $this->departments($rows->pluck('institution_department_id')->map(fn ($id): int => (int) $id)->unique()->values()->all());

        $gaps = [];

        foreach ($rows as $row) {
            $departmentId = (int) $row->institution_department_id;
            $department = $departments[$departmentId] ?? null;

            if ($department === null) {
                continue;
            }

            $gaps[] = new SetupGapResult(
                check: $this->key(),
                tenantId: $department['tenantId'],
                title: __('setup_gaps.offering_references_deleted_level_title'),
                body: __('setup_gaps.offering_references_deleted_level_body'),
                institutionDepartmentId: $departmentId,
                url: $this->applicationOfferingUrl($departmentId),
                meta: [
                    'offeringLevelId' => (int) $row->offering_level_id,
                    'departmentLevelId' => (int) $row->department_level_id,
                ],
                fingerprintKey: "offering-level-deleted:{$row->offering_level_id}",
            );
        }

        return $gaps;
    }

    /**
     * @return list<SetupGapResult>
     */
    private function offeringCoursesOnDeletedCourses(): array
    {
        $rows = DB::table('application_offering_courses as aoc')
            ->join('application_offering_levels as aol', 'aol.id', '=', 'aoc.application_offering_level_id')
            ->join('application_offering_departments as aod', 'aod.id', '=', 'aol.application_offering_department_id')
            ->join('department_courses as dc', 'dc.id', '=', 'aoc.department_course_id')
            ->whereNull('aoc.deleted_at')
            ->whereNull('aol.deleted_at')
            ->whereNull('aod.deleted_at')
            ->whereNotNull('dc.deleted_at')
            ->select(['aoc.id as offering_course_id', 'aoc.department_course_id', 'aod.institution_department_id'])
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $departments = $this->departments($rows->pluck('institution_department_id')->map(fn ($id): int => (int) $id)->unique()->values()->all());

        $gaps = [];

        foreach ($rows as $row) {
            $departmentId = (int) $row->institution_department_id;
            $department = $departments[$departmentId] ?? null;

            if ($department === null) {
                continue;
            }

            $gaps[] = new SetupGapResult(
                check: $this->key(),
                tenantId: $department['tenantId'],
                title: __('setup_gaps.offering_references_deleted_course_title'),
                body: __('setup_gaps.offering_references_deleted_course_body'),
                institutionDepartmentId: $departmentId,
                url: $this->applicationOfferingUrl($departmentId),
                meta: [
                    'offeringCourseId' => (int) $row->offering_course_id,
                    'departmentCourseId' => (int) $row->department_course_id,
                ],
                fingerprintKey: "offering-course-deleted:{$row->offering_course_id}",
            );
        }

        return $gaps;
    }
}
