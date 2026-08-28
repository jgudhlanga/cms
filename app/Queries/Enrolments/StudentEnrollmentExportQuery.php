<?php

declare(strict_types=1);

namespace App\Queries\Enrolments;

use App\Enums\Shared\ClassListTypeEnum;
use App\Models\Students\StudentEnrolment;
use App\Support\Maintenance\StudentAttributeFilterApplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class StudentEnrollmentExportQuery
{
    public function __construct(
        protected StudentAttributeFilterApplier $studentFilters = new StudentAttributeFilterApplier,
    ) {}

    /**
     * @param  array<string, mixed>|string|null  $filters  Legacy callers may still pass an intake year string.
     */
    public function baseQuery(array|string|null $filters = null): Builder
    {
        $filters = $this->resolveFilters($filters);

        return $this->matchingQuery($filters)
            ->with([
                'student.user',
                'student.gender',
                'student.addresses',
                'student.nextOfKins.relationship',
                'student.nextOfKins.contacts',
                'student.nextOfKins.addresses',
                'student.sponsors',
                'studentApplication.intakePeriod',
                'studentApplication.modeOfStudy',
                'departmentCourse.course',
                'semester',
                'academicCalendar',
                'academicCalendarStudentEnrolment.academicCalendarClass.classConfig',
            ])
            ->orderBy('student_enrolments.id');
    }

    /**
     * @param  array<string, mixed>|string|null  $filters
     */
    public function count(array|string|null $filters = null): int
    {
        return $this->matchingQuery($this->resolveFilters($filters))->count();
    }

    /**
     * Lightweight paginated rows used by the export preview screen.
     *
     * @param  array<string, mixed>  $filters
     */
    public function preview(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->matchingQuery($filters)
            ->with([
                'student.user',
                'student.gender',
                'institutionDepartment.department',
                'departmentLevel.level',
                'departmentCourse.course',
                'modeOfStudy',
                'semester',
                'academicCalendar',
            ])
            ->orderBy('student_enrolments.id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{total: int, byLevel: list<array{name: string, count: int}>, byGender: list<array{name: string, count: int}>, byModeOfStudy: list<array{name: string, count: int}>}
     */
    public function stats(array $filters): array
    {
        return [
            'total' => $this->matchingQuery($filters)->count(),
            'byLevel' => $this->breakdown($filters, function ($query): string {
                $query->leftJoin('department_levels', 'department_levels.id', '=', 'student_enrolments.department_level_id')
                    ->leftJoin('levels', 'levels.id', '=', 'department_levels.level_id');

                return 'levels.name';
            }),
            'byGender' => $this->breakdown($filters, function ($query): string {
                $query->leftJoin('students', 'students.id', '=', 'student_enrolments.student_id')
                    ->leftJoin('genders', 'genders.id', '=', 'students.gender_id');

                return 'genders.title';
            }),
            'byModeOfStudy' => $this->breakdown($filters, function ($query): string {
                $query->leftJoin('mode_of_studies', 'mode_of_studies.id', '=', 'student_enrolments.mode_of_study_id');

                return 'mode_of_studies.name';
            }),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function matchingQuery(array $filters): Builder
    {
        $query = StudentEnrolment::query()
            ->whereNull('student_enrolments.deleted_at')
            ->whereHas('studentApplication', function (Builder $applicationQuery) use ($filters): void {
                $applicationQuery
                    ->whereNull('student_applications.deleted_at')
                    ->whereHas('classList', function (Builder $classListQuery): void {
                        $classListQuery
                            ->where('type', ClassListTypeEnum::FINAL->value)
                            ->whereNull('class_lists.deleted_at');
                    });

                $intakeYear = $filters['intake_year'] ?? null;

                if (is_string($intakeYear) && $intakeYear !== '') {
                    $applicationQuery->whereHas('intakePeriod', fn (Builder $intakeQuery) => $intakeQuery
                        ->where('calendar_year', $intakeYear)
                        ->whereNull('intake_periods.deleted_at'));
                }
            });

        $this->applyProgrammeFilters($query, $filters);
        $this->applyCalendarFilters($query, $filters);

        if ($this->studentFilters->hasFilters($filters)) {
            $query->whereHas('student', fn (Builder $studentQuery) => $this->studentFilters->apply($studentQuery, $filters));
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyProgrammeFilters(Builder $query, array $filters): void
    {
        if ($this->idList($filters, 'department') !== []) {
            $query->whereIn('student_enrolments.institution_department_id', $this->idList($filters, 'department'));
        }

        if ($this->idList($filters, 'level') !== []) {
            $levelIds = $this->idList($filters, 'level');
            $query->whereHas('departmentLevel', fn (Builder $levelQuery) => $levelQuery->whereIn('level_id', $levelIds));
        }

        if ($this->idList($filters, 'course') !== []) {
            $query->whereIn('student_enrolments.department_course_id', $this->idList($filters, 'course'));
        }

        if ($this->idList($filters, 'mode_of_study') !== []) {
            $query->whereIn('student_enrolments.mode_of_study_id', $this->idList($filters, 'mode_of_study'));
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyCalendarFilters(Builder $query, array $filters): void
    {
        $calendarYear = $filters['calendar_year'] ?? null;

        if (is_string($calendarYear) && $calendarYear !== '') {
            $query->whereHas('academicCalendar', fn (Builder $calendarQuery) => $calendarQuery
                ->where('calendar_year', $calendarYear));
        }

        $semesterId = $filters['semester_id'] ?? null;

        if (is_numeric($semesterId) && (int) $semesterId > 0) {
            $query->where('student_enrolments.semester_id', (int) $semesterId);
        }

        $calendarType = $filters['calendar_type'] ?? null;

        if (is_string($calendarType) && $calendarType !== '') {
            $query->where(function (Builder $builder) use ($calendarType): void {
                $builder
                    ->whereHas('academicCalendar', fn (Builder $calendarQuery) => $calendarQuery
                        ->where('type', $calendarType))
                    ->orWhere(function (Builder $fallback) use ($calendarType): void {
                        $fallback
                            ->whereNull('student_enrolments.academic_calendar_id')
                            ->whereHas('departmentLevel.level', fn (Builder $levelQuery) => $levelQuery
                                ->where('calendar_type', $calendarType));
                    });
            });
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  callable(\Illuminate\Database\Query\Builder): string  $joinLabelColumn
     * @return list<array{name: string, count: int}>
     */
    private function breakdown(array $filters, callable $joinLabelColumn): array
    {
        $query = $this->matchingQuery($filters)->toBase();
        $labelColumn = $joinLabelColumn($query);

        return $query
            ->selectRaw($labelColumn.' as label, COUNT(*) as aggregate')
            ->groupBy($labelColumn)
            ->orderByDesc('aggregate')
            ->get()
            ->map(static fn ($row): array => [
                'name' => (string) ($row->label ?: '—'),
                'count' => (int) $row->aggregate,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<int>
     */
    private function idList(array $filters, string $key): array
    {
        $value = $filters[$key] ?? null;

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map('intval', $value), static fn (int $id): bool => $id > 0));
    }

    /**
     * @param  array<string, mixed>|string|null  $filters
     * @return array<string, mixed>
     */
    private function resolveFilters(array|string|null $filters): array
    {
        if (is_string($filters)) {
            return $filters === '' ? [] : ['intake_year' => $filters];
        }

        return $filters ?? [];
    }
}
