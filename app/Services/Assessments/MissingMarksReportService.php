<?php

namespace App\Services\Assessments;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Rbac\RoleEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\AssessmentType;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Staff;
use BackedEnum;

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
        $rows = $this->rows($filters);

        return [
            'rows' => $rows,
            'filters' => [
                'academicCalendarId' => $this->nullableInt($filters['academic_calendar_id'] ?? null),
                'assessmentTypeId' => $this->nullableInt($filters['assessment_type_id'] ?? null),
                'departmentId' => $this->nullableInt($filters['institution_department_id'] ?? null),
                'lecturerStaffId' => $this->nullableInt($filters['lecturer_staff_id'] ?? null),
            ],
            'filterOptions' => $this->filterOptions(),
            'canExport' => auth()->user()?->can('export:missing-marks-report') ?? false,
            'canEscalate' => auth()->user()?->can('escalate:missing-marks') ?? false,
            'canRemind' => auth()->user()?->can('remind:missing-marks') ?? false,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function rows(array $filters): array
    {
        $academicCalendarId = $this->nullableInt($filters['academic_calendar_id'] ?? null);
        $assessmentTypeId = $this->nullableInt($filters['assessment_type_id'] ?? null);
        $departmentId = $this->nullableInt($filters['institution_department_id'] ?? null);
        $lecturerStaffId = $this->nullableInt($filters['lecturer_staff_id'] ?? null);

        $calendars = AssessmentCalendar::query()
            ->with(['assessmentType', 'academicCalendar'])
            ->when($academicCalendarId !== null, fn ($query) => $query->where('academic_calendar_id', $academicCalendarId))
            ->when($assessmentTypeId !== null, fn ($query) => $query->where('assessment_type_id', $assessmentTypeId))
            ->orderBy('end_date')
            ->get();

        $rows = [];

        foreach ($calendars as $calendar) {
            $grouped = $this->missingMarksQuery->groupedByClassModule(
                $this->missingMarksQuery->forCalendar($calendar),
            );
            $lastTier = $this->notificationService->lastDispatchedTier($calendar);
            $escalated = $this->notificationService->hasEscalated($calendar);

            foreach ($grouped as $group) {
                if ($departmentId !== null && (int) $group['institutionDepartmentId'] !== $departmentId) {
                    continue;
                }

                if ($lecturerStaffId !== null && ! in_array($lecturerStaffId, $group['lecturerStaffIds'] ?? [], true)) {
                    continue;
                }

                $rows[] = [
                    'assessmentCalendarId' => (int) $calendar->id,
                    'assessmentTypeName' => (string) ($calendar->assessmentType?->name ?? ''),
                    'className' => (string) $group['className'],
                    'moduleName' => (string) $group['moduleName'],
                    'moduleCode' => (string) $group['moduleCode'],
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
     * @return array<string, list<array{id: int, label: string}>>
     */
    public function filterOptions(): array
    {
        return [
            'academicCalendars' => AcademicCalendar::query()
                ->orderByDesc('calendar_year')
                ->orderBy('opening_date')
                ->get()
                ->map(fn (AcademicCalendar $calendar): array => [
                    'id' => (int) $calendar->id,
                    'label' => $this->academicCalendarLabel($calendar),
                ])
                ->values()
                ->all(),
            'assessmentTypes' => AssessmentType::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (AssessmentType $type): array => [
                    'id' => (int) $type->id,
                    'label' => (string) $type->name,
                ])
                ->values()
                ->all(),
            'departments' => InstitutionDepartment::query()
                ->with('department')
                ->get()
                ->map(fn (InstitutionDepartment $department): array => [
                    'id' => (int) $department->id,
                    'label' => (string) ($department->department?->name ?? $department->department_code),
                ])
                ->sortBy('label')
                ->values()
                ->all(),
            'lecturers' => Staff::query()
                ->whereHas('user', fn ($query) => $query->role(RoleEnum::LECTURER->name()))
                ->with('user')
                ->get()
                ->map(fn (Staff $staff): array => [
                    'id' => (int) $staff->id,
                    'label' => (string) ($staff->user?->full_name ?? __('dashboard.academic_unknown_lecturer')),
                ])
                ->sortBy('label')
                ->values()
                ->all(),
        ];
    }

    private function academicCalendarLabel(AcademicCalendar $calendar): string
    {
        $type = $calendar->type instanceof BackedEnum ? $calendar->type->value : (string) $calendar->type;

        return trim($calendar->calendar_year.' · '.ucfirst($type !== '' ? $type : AcademicCalendarTypeEnum::SEMESTER->value));
    }

    private function nullableInt(mixed $value): ?int
    {
        $id = (int) $value;

        return $id > 0 ? $id : null;
    }
}
