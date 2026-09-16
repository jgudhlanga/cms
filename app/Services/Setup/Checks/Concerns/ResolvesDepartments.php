<?php

declare(strict_types=1);

namespace App\Services\Setup\Checks\Concerns;

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
}
