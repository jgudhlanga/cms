<?php

declare(strict_types=1);

namespace App\Services\Institution;

use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Scopes\Tenant\TenantScope;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use Illuminate\Validation\ValidationException;

/**
 * Department levels and courses carry the programme identity of every
 * application and enrolment that points at them. Unlinking one leaves those
 * records without a level or course, so usage has to be checked first.
 */
class ProgrammeLinkUsageGuard
{
    /**
     * @param  list<int>  $departmentLevelIds
     * @return array<int, int>
     */
    public function levelUsage(array $departmentLevelIds): array
    {
        return $this->usage($departmentLevelIds, 'department_level_id');
    }

    /**
     * @param  list<int>  $departmentCourseIds
     * @return array<int, int>
     */
    public function courseUsage(array $departmentCourseIds): array
    {
        return $this->usage($departmentCourseIds, 'department_course_id');
    }

    /**
     * @param  list<int>  $departmentLevelIds
     *
     * @throws ValidationException
     */
    public function assertLevelsUnused(array $departmentLevelIds, string $errorKey = 'level_ids'): void
    {
        $usage = $this->levelUsage($departmentLevelIds);

        if ($usage === []) {
            return;
        }

        $names = DepartmentLevel::query()
            ->withTrashed()
            ->with('level')
            ->whereKey(array_keys($usage))
            ->get()
            ->map(fn (DepartmentLevel $level): string => trim((string) $level->level?->name) !== ''
                ? (string) $level->level?->name
                : __('trans.level_number', ['id' => $level->id]))
            ->all();

        $this->fail($errorKey, $names, array_sum($usage));
    }

    /**
     * @param  list<int>  $departmentCourseIds
     *
     * @throws ValidationException
     */
    public function assertCoursesUnused(array $departmentCourseIds, string $errorKey = 'course_ids'): void
    {
        $usage = $this->courseUsage($departmentCourseIds);

        if ($usage === []) {
            return;
        }

        $names = DepartmentCourse::query()
            ->withTrashed()
            ->with('course')
            ->whereKey(array_keys($usage))
            ->get()
            ->map(fn (DepartmentCourse $course): string => trim((string) $course->course?->name) !== ''
                ? (string) $course->course?->name
                : __('trans.course_number', ['id' => $course->id]))
            ->all();

        $this->fail($errorKey, $names, array_sum($usage));
    }

    /**
     * Counts of live applications and enrolments per link, keyed by link id.
     *
     * @param  list<int>  $ids
     * @return array<int, int>
     */
    private function usage(array $ids, string $column): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn (int $id): bool => $id > 0)));

        if ($ids === []) {
            return [];
        }

        $counts = [];

        foreach ([StudentApplication::class, StudentEnrolment::class] as $model) {
            // Tenant scoping would hide records owned by another tenant and let
            // an in-use link be unlinked; soft-delete scoping must stay on.
            $rows = $model::query()
                ->withoutGlobalScope(TenantScope::class)
                ->whereIn($column, $ids)
                ->selectRaw("{$column} as link_id, COUNT(*) as aggregate")
                ->groupBy($column)
                ->pluck('aggregate', 'link_id');

            foreach ($rows as $linkId => $aggregate) {
                $counts[(int) $linkId] = ($counts[(int) $linkId] ?? 0) + (int) $aggregate;
            }
        }

        return array_filter($counts, fn (int $count): bool => $count > 0);
    }

    /**
     * @param  list<string>  $names
     *
     * @throws ValidationException
     */
    private function fail(string $errorKey, array $names, int $records): void
    {
        throw ValidationException::withMessages([
            $errorKey => __('trans.programme_link_in_use', [
                'names' => implode(', ', $names),
                'count' => $records,
            ]),
        ]);
    }
}
