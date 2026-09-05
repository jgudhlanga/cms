<?php

declare(strict_types=1);

namespace App\Services\Institution;

use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\ModeOfStudy;
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
     * Live applications and enrolments for a course + level pair, keyed by department level id.
     *
     * @param  list<int>  $departmentLevelIds
     * @return array<int, int>
     */
    public function courseLevelUsage(int $departmentCourseId, array $departmentLevelIds): array
    {
        return $this->usageFor(
            $departmentLevelIds,
            'department_level_id',
            ['department_course_id' => $departmentCourseId],
        );
    }

    /**
     * @param  list<int>  $departmentLevelIds
     *
     * @throws ValidationException
     */
    public function assertCourseLevelsUnused(
        int $departmentCourseId,
        array $departmentLevelIds,
        string $errorKey = 'department_level_ids',
        string $messageKey = 'trans.programme_course_level_in_use',
    ): void {
        $split = $this->usageSplitFor(
            $departmentLevelIds,
            'department_level_id',
            ['department_course_id' => $departmentCourseId],
        );

        if ($split === []) {
            return;
        }

        $levelNames = DepartmentLevel::query()
            ->withTrashed()
            ->with('level')
            ->whereKey(array_keys($split))
            ->get()
            ->map(fn (DepartmentLevel $level): string => $this->levelName($level))
            ->all();

        throw ValidationException::withMessages([
            $errorKey => __($messageKey, [
                'course' => $this->courseName($departmentCourseId),
                'levels' => implode(', ', $levelNames),
                'usage' => $this->usagePhrase($split),
            ]),
        ]);
    }

    /**
     * Live applications and enrolments for a course + level + mode triple, keyed by mode id.
     *
     * @param  list<int>  $modeOfStudyIds
     * @return array<int, int>
     */
    public function courseLevelModeUsage(
        int $departmentCourseId,
        int $departmentLevelId,
        array $modeOfStudyIds,
    ): array {
        return $this->usageFor(
            $modeOfStudyIds,
            'mode_of_study_id',
            [
                'department_course_id' => $departmentCourseId,
                'department_level_id' => $departmentLevelId,
            ],
        );
    }

    /**
     * @return list<int>
     */
    public function usedModeIds(int $departmentCourseId, int $departmentLevelId): array
    {
        $ids = [];

        foreach ([StudentApplication::class, StudentEnrolment::class] as $model) {
            $ids = [
                ...$ids,
                ...$model::query()
                    ->withoutGlobalScope(TenantScope::class)
                    ->where('department_course_id', $departmentCourseId)
                    ->where('department_level_id', $departmentLevelId)
                    ->whereNotNull('mode_of_study_id')
                    ->pluck('mode_of_study_id')
                    ->map(fn (mixed $id): int => (int) $id)
                    ->filter(fn (int $id): bool => $id > 0)
                    ->all(),
            ];
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  list<int>  $modeOfStudyIds
     * @return array<int, array{applications: int, enrolments: int}>
     */
    public function courseLevelModeUsageSplit(
        int $departmentCourseId,
        int $departmentLevelId,
        array $modeOfStudyIds,
    ): array {
        return $this->usageSplitFor(
            $modeOfStudyIds,
            'mode_of_study_id',
            [
                'department_course_id' => $departmentCourseId,
                'department_level_id' => $departmentLevelId,
            ],
        );
    }

    /**
     * @param  list<int>  $modeOfStudyIds
     *
     * @throws ValidationException
     */
    public function assertCourseLevelModesUnused(
        int $departmentCourseId,
        int $departmentLevelId,
        array $modeOfStudyIds,
        string $errorKey = 'mode_ids',
    ): void {
        $split = $this->usageSplitFor(
            $modeOfStudyIds,
            'mode_of_study_id',
            [
                'department_course_id' => $departmentCourseId,
                'department_level_id' => $departmentLevelId,
            ],
        );

        if ($split === []) {
            return;
        }

        $modeNames = ModeOfStudy::query()
            ->withTrashed()
            ->whereKey(array_keys($split))
            ->get()
            ->map(fn (ModeOfStudy $mode): string => trim((string) $mode->name) !== ''
                ? (string) $mode->name
                : __('trans.mode_number', ['id' => $mode->id]))
            ->all();

        $departmentLevel = DepartmentLevel::query()
            ->withTrashed()
            ->with('level')
            ->find($departmentLevelId);

        throw ValidationException::withMessages([
            $errorKey => __('trans.programme_course_mode_in_use', [
                'course' => $this->courseName($departmentCourseId),
                'level' => $departmentLevel instanceof DepartmentLevel
                    ? $this->levelName($departmentLevel)
                    : __('trans.level_number', ['id' => $departmentLevelId]),
                'modes' => implode(', ', $modeNames),
                'usage' => $this->usagePhrase($split),
            ]),
        ]);
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, int>
     */
    private function usage(array $ids, string $column): array
    {
        return $this->totalsFromSplit($this->usageSplitFor($ids, $column));
    }

    /**
     * @param  list<int>  $ids
     * @param  array<string, int>  $constraints
     * @return array<int, int>
     */
    private function usageFor(array $ids, string $column, array $constraints = []): array
    {
        return $this->totalsFromSplit($this->usageSplitFor($ids, $column, $constraints));
    }

    /**
     * @param  list<int>  $ids
     * @param  array<string, int>  $constraints
     * @return array<int, array{applications: int, enrolments: int}>
     */
    private function usageSplitFor(array $ids, string $column, array $constraints = []): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn (int $id): bool => $id > 0)));

        if ($ids === []) {
            return [];
        }

        $applications = $this->countsBy(StudentApplication::class, $column, $ids, $constraints);
        $enrolments = $this->countsBy(StudentEnrolment::class, $column, $ids, $constraints);
        $split = [];

        foreach (array_unique([...array_keys($applications), ...array_keys($enrolments)]) as $id) {
            $id = (int) $id;
            $applicationCount = $applications[$id] ?? 0;
            $enrolmentCount = $enrolments[$id] ?? 0;

            if ($applicationCount < 1 && $enrolmentCount < 1) {
                continue;
            }

            $split[$id] = [
                'applications' => $applicationCount,
                'enrolments' => $enrolmentCount,
            ];
        }

        return $split;
    }

    /**
     * Tenant scoping would hide records owned by another tenant and let
     * an in-use link be unlinked; soft-delete scoping must stay on.
     *
     * @param  class-string<StudentApplication|StudentEnrolment>  $model
     * @param  list<int>  $ids
     * @param  array<string, int>  $constraints
     * @return array<int, int>
     */
    private function countsBy(string $model, string $column, array $ids, array $constraints): array
    {
        $query = $model::query()
            ->withoutGlobalScope(TenantScope::class);

        foreach ($constraints as $constraintColumn => $value) {
            $query->where($constraintColumn, $value);
        }

        $counts = [];

        foreach (
            $query
                ->whereIn($column, $ids)
                ->selectRaw("{$column} as link_id, COUNT(*) as aggregate")
                ->groupBy($column)
                ->pluck('aggregate', 'link_id') as $linkId => $aggregate
        ) {
            $counts[(int) $linkId] = (int) $aggregate;
        }

        return $counts;
    }

    /**
     * @param  array<int, array{applications: int, enrolments: int}>  $split
     * @return array<int, int>
     */
    private function totalsFromSplit(array $split): array
    {
        $counts = [];

        foreach ($split as $id => $parts) {
            $total = $parts['applications'] + $parts['enrolments'];

            if ($total > 0) {
                $counts[(int) $id] = $total;
            }
        }

        return $counts;
    }

    /**
     * @param  array<int, array{applications: int, enrolments: int}>  $split
     */
    private function usagePhrase(array $split): string
    {
        $applications = array_sum(array_column($split, 'applications'));
        $enrolments = array_sum(array_column($split, 'enrolments'));
        $parts = [];

        if ($applications > 0) {
            $parts[] = trans_choice('trans.programme_usage_applications', $applications, ['count' => $applications]);
        }

        if ($enrolments > 0) {
            $parts[] = trans_choice('trans.programme_usage_enrolments', $enrolments, ['count' => $enrolments]);
        }

        return implode(' '.__('trans.and').' ', $parts);
    }

    private function courseName(int $departmentCourseId): string
    {
        $course = DepartmentCourse::query()
            ->withTrashed()
            ->with('course')
            ->find($departmentCourseId);

        $name = trim((string) $course?->course?->name);

        return $name !== ''
            ? $name
            : __('trans.course_number', ['id' => $departmentCourseId]);
    }

    private function levelName(DepartmentLevel $level): string
    {
        $name = trim((string) $level->level?->name);

        return $name !== ''
            ? $name
            : __('trans.level_number', ['id' => $level->id]);
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
