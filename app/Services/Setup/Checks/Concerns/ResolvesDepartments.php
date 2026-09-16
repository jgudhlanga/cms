<?php

declare(strict_types=1);

namespace App\Services\Setup\Checks\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Department lookups shared by the checks. Several of the scanned tables (course_level_modes,
 * class_configs) carry no tenant column, so the department is what ties their gaps to a tenant.
 */
trait ResolvesDepartments
{
    /**
     * @param  list<int>  $departmentIds
     * @return array<int, array{name: string, tenantId: int}>
     */
    protected function departments(array $departmentIds): array
    {
        if ($departmentIds === []) {
            return [];
        }

        return DB::table('institution_departments as id_')
            ->join('departments as d', 'd.id', '=', 'id_.department_id')
            ->whereIn('id_.id', $departmentIds)
            ->select('id_.id', 'id_.tenant_id', 'd.name')
            ->get()
            ->mapWithKeys(fn (object $row): array => [
                (int) $row->id => ['name' => (string) $row->name, 'tenantId' => (int) $row->tenant_id],
            ])
            ->all();
    }

    protected function departmentUrl(int $institutionDepartmentId): string
    {
        return route('institution-departments.show', ['department' => $institutionDepartmentId]);
    }

    protected function departmentClassesUrl(int $institutionDepartmentId, string $calendarYear): string
    {
        return route('academic-calendars.department-classes', [
            'institution_department' => $institutionDepartmentId,
            'calendar_year' => $calendarYear,
        ]);
    }

    protected function applicationOfferingUrl(int $institutionDepartmentId): string
    {
        return route('application-offerings.show', ['institution_department' => $institutionDepartmentId]);
    }

    /**
     * @param  list<int>  $departmentCourseIds
     * @return array<int, string>
     */
    protected function courseNames(array $departmentCourseIds): array
    {
        if ($departmentCourseIds === []) {
            return [];
        }

        return DB::table('department_courses as dc')
            ->join('courses as c', 'c.id', '=', 'dc.course_id')
            ->whereIn('dc.id', $departmentCourseIds)
            ->pluck('c.name', 'dc.id')
            ->mapWithKeys(fn (?string $name, mixed $id): array => [(int) $id => (string) $name])
            ->all();
    }

    /**
     * @param  list<int>  $departmentLevelIds
     * @return array<int, string>
     */
    protected function levelNames(array $departmentLevelIds): array
    {
        if ($departmentLevelIds === []) {
            return [];
        }

        return DB::table('department_levels as dl')
            ->join('levels as l', 'l.id', '=', 'dl.level_id')
            ->whereIn('dl.id', $departmentLevelIds)
            ->pluck('l.name', 'dl.id')
            ->mapWithKeys(fn (?string $name, mixed $id): array => [(int) $id => (string) $name])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected function modeNames(): array
    {
        return DB::table('mode_of_studies')
            ->pluck('name', 'id')
            ->mapWithKeys(fn (?string $name, mixed $id): array => [(int) $id => (string) $name])
            ->all();
    }

    /**
     * Live mode configuration keyed by "courseId:levelId".
     *
     * @return Collection<string, list<int>>
     */
    protected function configuredModes(): Collection
    {
        return DB::table('course_level_modes')
            ->whereNull('deleted_at')
            ->select('department_course_id', 'department_level_id', 'modes')
            ->get()
            ->mapWithKeys(function (object $row): array {
                $modes = json_decode((string) ($row->modes ?? '[]'), true);
                $ids = is_array($modes)
                    ? array_values(array_filter(array_map('intval', $modes), static fn (int $id): bool => $id > 0))
                    : [];

                return ["{$row->department_course_id}:{$row->department_level_id}" => $ids];
            });
    }
}
