<?php

namespace App\Services\Assessments;

use App\Enums\Institution\CourseSyllabusStatusEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Staff;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Support\Rbac\UserAccessScope;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Missing coursework marks for one calendar year: the current year by default, with earlier years kept for
 * reference where assessment calendars exist. Filters follow the department's set-up: a department lists its
 * levels, a level its courses, a course its syllabus modules and a module its lecturers. Each list waits for the
 * one before it, and a choice that is not on offer is ignored.
 */
class MissingMarksReportService
{
    public function __construct(
        private readonly MissingMarksQueryService $missingMarksQuery,
        private readonly MissingMarksNotificationService $notificationService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function page(array $filters): array
    {
        $user = auth()->user();

        return [
            ...$this->report($filters),
            'canExport' => $user?->can('export:missing-marks-report') ?? false,
            'canEscalate' => $user?->can('escalate:missing-marks') ?? false,
            'canRemind' => $user?->can('remind:missing-marks') ?? false,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function rows(array $filters): array
    {
        return $this->report($filters)['rows'];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     filters: array<string, int|null>,
     *     filterOptions: array<string, list<array{id: int, label: string}>>,
     *     availableYears: list<int>,
     *     currentYear: int,
     *     isHistorical: bool
     * }
     */
    public function report(array $filters): array
    {
        $currentYear = now()->year;
        $availableYears = $this->availableYears($currentYear);
        $requestedYear = $this->nullableInt($filters['calendar_year'] ?? null);
        $calendarYear = in_array($requestedYear, $availableYears, true) ? $requestedYear : $currentYear;

        $calendars = $this->calendarsForYear($calendarYear);
        $rows = collect($this->rowsForCalendars($calendars));

        $departments = $this->departmentOptions();
        $departmentId = $this->selectedOption($filters['institution_department_id'] ?? null, $departments);

        // Someone who can reach a single academic department (typically a HOD) starts with it chosen.
        if ($departmentId === null && count($departments) === 1) {
            $departmentId = $departments[0]['id'];
        }

        $rows = $this->narrow($rows, 'institutionDepartmentId', $departmentId);

        $levels = $departmentId === null ? [] : $this->levelOptions($departmentId);
        $levelId = $this->selectedOption($filters['level_id'] ?? null, $levels);
        $rows = $this->narrow($rows, 'levelId', $levelId);

        $courses = $departmentId === null || $levelId === null ? [] : $this->courseOptions($departmentId, $levelId);
        $courseId = $this->selectedOption($filters['course_id'] ?? null, $courses);
        $rows = $this->narrow($rows, 'courseId', $courseId);

        $modules = $departmentId === null || $levelId === null || $courseId === null
            ? []
            : $this->moduleOptions($departmentId, $levelId, $courseId, $calendarYear);
        $moduleId = $this->selectedOption($filters['module_id'] ?? null, $modules);
        $rows = $this->narrow($rows, 'moduleId', $moduleId);

        $lecturers = $departmentId === null || $moduleId === null
            ? []
            : $this->lecturerOptions($departmentId, $moduleId, $calendarYear);
        $lecturerStaffId = $this->selectedOption($filters['lecturer_staff_id'] ?? null, $lecturers);
        $rows = $lecturerStaffId === null
            ? $rows
            : $rows->filter(fn (array $row): bool => in_array($lecturerStaffId, $row['lecturerStaffIds'], true))->values();

        // Assessment types come from the year's calendars, so a type with nothing missing can still be chosen.
        $assessmentTypes = $calendars
            ->map(fn (AssessmentCalendar $calendar): array => [
                'id' => (int) $calendar->assessment_type_id,
                'label' => (string) ($calendar->assessmentType?->name ?? ''),
            ])
            ->unique('id')
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
        $assessmentTypeId = $this->selectedOption($filters['assessment_type_id'] ?? null, $assessmentTypes);
        $rows = $this->narrow($rows, 'assessmentTypeId', $assessmentTypeId);

        return [
            'rows' => $rows
                ->map(fn (array $row): array => Arr::except($row, [
                    'institutionDepartmentId',
                    'levelId',
                    'courseId',
                    'moduleId',
                    'lecturers',
                    'lecturerStaffIds',
                ]))
                ->values()
                ->all(),
            'filters' => [
                'calendarYear' => $calendarYear,
                'departmentId' => $departmentId,
                'levelId' => $levelId,
                'courseId' => $courseId,
                'moduleId' => $moduleId,
                'lecturerStaffId' => $lecturerStaffId,
                'assessmentTypeId' => $assessmentTypeId,
            ],
            'filterOptions' => [
                'departments' => $departments,
                'levels' => $levels,
                'courses' => $courses,
                'modules' => $modules,
                'lecturers' => $lecturers,
                'assessmentTypes' => $assessmentTypes,
            ],
            'availableYears' => $availableYears,
            'currentYear' => $currentYear,
            'isHistorical' => $calendarYear < $currentYear,
        ];
    }

    /**
     * The current year, plus earlier years that have assessment calendars.
     *
     * @return list<int>
     */
    private function availableYears(int $currentYear): array
    {
        return AcademicCalendar::query()
            ->whereIn('id', AssessmentCalendar::query()->select('academic_calendar_id'))
            ->distinct()
            ->pluck('calendar_year')
            ->map(fn ($year): int => (int) $year)
            ->filter(fn (int $year): bool => $year > 0 && $year <= $currentYear)
            ->push($currentYear)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, AssessmentCalendar>
     */
    private function calendarsForYear(int $calendarYear): Collection
    {
        return AssessmentCalendar::query()
            ->with(['assessmentType', 'academicCalendar'])
            ->whereIn(
                'academic_calendar_id',
                AcademicCalendar::query()->where('calendar_year', (string) $calendarYear)->select('id'),
            )
            ->orderBy('end_date')
            ->get();
    }

    /**
     * @param  Collection<int, AssessmentCalendar>  $calendars
     * @return list<array<string, mixed>>
     */
    private function rowsForCalendars(Collection $calendars): array
    {
        $rows = [];

        foreach ($calendars as $calendar) {
            $grouped = $this->missingMarksQuery->groupedByClassModule(
                $this->missingMarksQuery->forCalendarForCurrentUser($calendar),
            );
            $lastTier = $this->notificationService->lastDispatchedTier($calendar);
            $escalated = $this->notificationService->hasEscalated($calendar);

            foreach ($grouped as $group) {
                $rows[] = [
                    'assessmentCalendarId' => (int) $calendar->id,
                    'assessmentTypeId' => (int) $calendar->assessment_type_id,
                    'assessmentTypeName' => (string) ($calendar->assessmentType?->name ?? ''),
                    'institutionDepartmentId' => (int) $group['institutionDepartmentId'],
                    'departmentName' => (string) $group['departmentName'],
                    'levelId' => (int) ($group['levelId'] ?? 0),
                    'levelName' => (string) ($group['levelName'] ?? ''),
                    'courseId' => (int) ($group['courseId'] ?? 0),
                    'courseName' => (string) ($group['courseName'] ?? ''),
                    'className' => (string) $group['className'],
                    'moduleId' => (int) $group['moduleId'],
                    'moduleName' => (string) $group['moduleName'],
                    'moduleCode' => (string) $group['moduleCode'],
                    'lecturers' => $group['lecturers'] ?? [],
                    'lecturerStaffIds' => array_map('intval', $group['lecturerStaffIds'] ?? []),
                    'lecturerNames' => implode(', ', $group['lecturerNames'] ?? []) ?: '—',
                    'incompleteCount' => (int) $group['incompleteCount'],
                    'dueDate' => $calendar->end_date?->toDateString(),
                    'lastTier' => $lastTier?->value,
                    'lastTierLabel' => $lastTier?->label(),
                    'escalated' => $escalated,
                ];
            }
        }

        return $rows;
    }

    /**
     * Academic departments the viewer can reach.
     *
     * @return list<array{id: int, label: string}>
     */
    private function departmentOptions(): array
    {
        $departmentIds = UserAccessScope::for(auth()->user())->departmentIds();

        return InstitutionDepartment::query()
            ->with('department')
            ->whereHas('department', fn ($query) => $query->where('is_academic', true))
            ->when($departmentIds !== null, fn ($query) => $query->whereIn('id', $departmentIds ?: [-1]))
            ->get()
            ->map(fn (InstitutionDepartment $department): array => [
                'id' => (int) $department->id,
                'label' => (string) ($department->department?->name ?? $department->department_code),
            ])
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * Levels the department offers.
     *
     * @return list<array{id: int, label: string}>
     */
    private function levelOptions(int $institutionDepartmentId): array
    {
        return $this->options(
            DepartmentLevel::query()->with('level')->where('institution_department_id', $institutionDepartmentId)->get(),
            fn (DepartmentLevel $departmentLevel): array => [
                'id' => (int) $departmentLevel->level_id,
                'label' => (string) ($departmentLevel->level?->name ?? ''),
            ],
        );
    }

    /**
     * Courses the department offers at the level.
     *
     * @return list<array{id: int, label: string}>
     */
    private function courseOptions(int $institutionDepartmentId, int $levelId): array
    {
        return $this->options(
            $this->levelCourses($institutionDepartmentId, $levelId),
            fn (DepartmentLevelCourse $link): array => [
                'id' => (int) ($link->departmentCourse?->course_id ?? 0),
                'label' => (string) ($link->departmentCourse?->course?->name ?? ''),
            ],
        );
    }

    /**
     * Modules in the course's active syllabi, plus any syllabus that year's classes were set up with (so earlier
     * years still list the modules they were taught from).
     *
     * @return list<array{id: int, label: string}>
     */
    private function moduleOptions(int $institutionDepartmentId, int $levelId, int $courseId, int $calendarYear): array
    {
        $links = $this->levelCourses($institutionDepartmentId, $levelId, $courseId);

        if ($links->isEmpty()) {
            return [];
        }

        $activeSyllabusIds = CourseSyllabus::query()
            ->whereIn('department_level_course_id', $links->pluck('id'))
            ->where('status', CourseSyllabusStatusEnum::Active)
            ->pluck('id');

        $yearSyllabusIds = ClassConfig::query()
            ->where('calendar_year', (string) $calendarYear)
            ->where('institution_department_id', $institutionDepartmentId)
            ->whereIn('department_level_id', $links->pluck('department_level_id'))
            ->whereIn('department_course_id', $links->pluck('department_course_id'))
            ->get(['id', 'course_syllabus_ids'])
            ->pluck('course_syllabus_ids')
            ->flatten();

        $syllabusIds = $activeSyllabusIds
            ->merge($yearSyllabusIds)
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        return $this->options(
            CourseSyllabusModule::query()->whereIn('course_syllabus_id', $syllabusIds)->get(['id', 'code', 'title']),
            fn (CourseSyllabusModule $module): array => [
                'id' => (int) $module->id,
                'label' => trim(($module->code ?? '').' · '.$module->title, ' ·'),
            ],
        );
    }

    /**
     * Lecturers assigned to the module, either for the module as a whole or on one of the department's classes
     * that year.
     *
     * @return list<array{id: int, label: string}>
     */
    private function lecturerOptions(int $institutionDepartmentId, int $moduleId, int $calendarYear): array
    {
        $yearClassIds = AcademicCalendarClass::query()
            ->whereIn(
                'class_config_id',
                ClassConfig::query()
                    ->where('calendar_year', (string) $calendarYear)
                    ->where('institution_department_id', $institutionDepartmentId)
                    ->select('id'),
            )
            ->select('id');

        $staffIds = DB::table('course_syllabus_module_lecturers')
            ->where('course_syllabus_module_id', $moduleId)
            ->where(fn ($query) => $query
                ->whereNull('academic_calendar_class_id')
                ->orWhereIn('academic_calendar_class_id', $yearClassIds))
            ->pluck('staff_id');

        return $this->options(
            Staff::query()->with('user')->whereIn('id', $staffIds)->get(),
            fn (Staff $staff): array => [
                'id' => (int) $staff->id,
                'label' => (string) ($staff->user?->full_name ?: __('dashboard.academic_unknown_lecturer')),
            ],
        );
    }

    /**
     * @return Collection<int, DepartmentLevelCourse>
     */
    private function levelCourses(int $institutionDepartmentId, int $levelId, ?int $courseId = null): Collection
    {
        return DepartmentLevelCourse::query()
            ->with('departmentCourse.course')
            ->whereHas('departmentLevel', fn ($query) => $query
                ->where('institution_department_id', $institutionDepartmentId)
                ->where('level_id', $levelId))
            ->when($courseId !== null, fn ($query) => $query->whereHas(
                'departmentCourse',
                fn ($courseQuery) => $courseQuery->where('course_id', $courseId),
            ))
            ->get();
    }

    /**
     * @template TItem
     *
     * @param  iterable<int, TItem>  $items
     * @param  callable(TItem): array{id: int, label: string}  $toOption
     * @return list<array{id: int, label: string}>
     */
    private function options(iterable $items, callable $toOption): array
    {
        return collect($items)
            ->map($toOption)
            ->filter(fn (array $option): bool => $option['id'] > 0 && $option['label'] !== '')
            ->unique('id')
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * @param  list<array{id: int, label: string}>  $options
     */
    private function selectedOption(mixed $value, array $options): ?int
    {
        $id = $this->nullableInt($value);

        return $id !== null && in_array($id, array_column($options, 'id'), true) ? $id : null;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function narrow(Collection $rows, string $key, ?int $id): Collection
    {
        return $id === null ? $rows : $rows->filter(fn (array $row): bool => (int) $row[$key] === $id)->values();
    }

    private function nullableInt(mixed $value): ?int
    {
        $id = (int) $value;

        return $id > 0 ? $id : null;
    }
}
