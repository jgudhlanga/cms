<?php

declare(strict_types=1);

namespace App\Http\Controllers\Institution\Departments;

use App\Enums\Assessments\AssessmentWindowStatusEnum;
use App\Enums\Assessments\MissingMarksNotificationTierEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\DepartmentAssessmentCalendarRequest;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\AssessmentCalendar\DepartmentAssessmentCalendar;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\ModeOfStudy;
use App\Models\Users\User;
use App\Notifications\Assessments\DepartmentAssessmentCalendarPublishedNotification;
use App\Services\Assessments\DepartmentCalendarSyncService;
use App\Services\Assessments\MissingMarksQueryService;
use App\Support\Rbac\UserAccessScope;
use Illuminate\Support\Facades\Notification;
use BackedEnum;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentAssessmentCalendarController extends Controller
{
    public function index(Request $request, InstitutionDepartment $department): Response
    {
        $this->authorize('viewAny', [DepartmentAssessmentCalendar::class, $department]);

        $calendarYear = (string) ($request->integer('calendar_year') ?: now()->year);
        $user = $request->user();
        $reachesDepartment = UserAccessScope::for($user)->canReachDepartment((int) $department->id);

        $departmentCalendars = DepartmentAssessmentCalendar::query()
            ->withTrashed()
            ->where('institution_department_id', $department->id)
            ->get()
            ->keyBy(fn (DepartmentAssessmentCalendar $calendar): int => (int) $calendar->assessment_calendar_id);

        $modeNames = ModeOfStudy::query()->pluck('name', 'id');

        $rows = AssessmentCalendar::query()
            ->with(['assessmentType', 'academicCalendar'])
            ->whereHas('academicCalendar', fn ($query) => $query->where('calendar_year', $calendarYear))
            ->orderBy('start_date')
            ->get()
            ->map(fn (AssessmentCalendar $calendar): array => $this->row(
                $calendar,
                $departmentCalendars->get((int) $calendar->id),
                $modeNames,
            ))
            ->values()
            ->all();

        return Inertia::render('institution/departments/assessment-calendars/Index', [
            'department' => InstitutionDepartmentResource::make($department->loadMissing('department')),
            'calendarYear' => (int) $calendarYear,
            'rows' => $rows,
            'can' => [
                'create' => $user?->can('create', [DepartmentAssessmentCalendar::class, $department]) ?? false,
                'update' => $reachesDepartment && ($user?->can('update:department-assessment-calendar') ?? false),
                'delete' => $reachesDepartment && ($user?->can('delete:department-assessment-calendar') ?? false),
                'restore' => $reachesDepartment && ($user?->can('restore:department-assessment-calendar') ?? false),
            ],
        ]);
    }

    public function store(DepartmentAssessmentCalendarRequest $request, InstitutionDepartment $department): RedirectResponse
    {
        $validated = $request->validated();
        $userId = $request->user()?->id;
        $attributes = $this->attributesFrom($validated, $userId);

        $trashed = DepartmentAssessmentCalendar::onlyTrashed()
            ->where('assessment_calendar_id', (int) $validated['assessment_calendar_id'])
            ->where('institution_department_id', $department->id)
            ->first();

        if ($trashed instanceof DepartmentAssessmentCalendar) {
            $trashed->restore();
            $trashed->update($attributes);
            $departmentCalendar = $trashed;
        } else {
            $departmentCalendar = DepartmentAssessmentCalendar::query()->create([
                ...$attributes,
                'assessment_calendar_id' => (int) $validated['assessment_calendar_id'],
                'institution_department_id' => $department->id,
                'created_by' => $userId,
            ]);
        }

        $this->notifyLecturers($departmentCalendar);

        return back()->with('success', __('academic_calendar.department_assessment_calendar_saved'));
    }

    /**
     * Lecturers who still have marks to capture under this calendar hear about the department's dates.
     */
    private function notifyLecturers(DepartmentAssessmentCalendar $departmentCalendar): void
    {
        $departmentCalendar->loadMissing(['assessmentCalendar.assessmentType', 'institutionDepartment.department']);
        $globalCalendar = $departmentCalendar->assessmentCalendar;

        if (! $globalCalendar instanceof AssessmentCalendar) {
            return;
        }

        $rows = app(MissingMarksQueryService::class)->forCalendar(
            $globalCalendar,
            [(int) $departmentCalendar->institution_department_id],
        );

        $lecturerUserIds = collect($rows)->pluck('lecturerUserIds')->flatten()->filter()->map(fn ($id): int => (int) $id)->unique()->values();

        if ($lecturerUserIds->isEmpty()) {
            return;
        }

        Notification::send(
            User::query()->whereIn('id', $lecturerUserIds->all())->get(),
            new DepartmentAssessmentCalendarPublishedNotification(
                (string) ($globalCalendar->assessmentType?->name ?? ''),
                (string) ($departmentCalendar->institutionDepartment?->department?->name ?? ''),
                Carbon::parse($departmentCalendar->start_date)->format('d M Y'),
                Carbon::parse($departmentCalendar->end_date)->format('d M Y'),
                $departmentCalendar->notes,
            ),
        );
    }

    public function update(
        DepartmentAssessmentCalendarRequest $request,
        InstitutionDepartment $department,
        DepartmentAssessmentCalendar $departmentAssessmentCalendar,
    ): RedirectResponse {
        abort_unless((int) $departmentAssessmentCalendar->institution_department_id === (int) $department->id, 404);

        $departmentAssessmentCalendar->update($this->attributesFrom($request->validated(), $request->user()?->id));

        if ($departmentAssessmentCalendar->wasChanged(['start_date', 'end_date'])) {
            $this->notifyLecturers($departmentAssessmentCalendar);
        }

        return back()->with('success', __('academic_calendar.department_assessment_calendar_saved'));
    }

    public function destroy(
        InstitutionDepartment $department,
        DepartmentAssessmentCalendar $departmentAssessmentCalendar,
    ): RedirectResponse {
        abort_unless((int) $departmentAssessmentCalendar->institution_department_id === (int) $department->id, 404);
        $this->authorize('delete', $departmentAssessmentCalendar);

        // Removing a closed department window would fall back to a still-open global window and reopen capture.
        if (Carbon::parse($departmentAssessmentCalendar->end_date)->endOfDay()->isPast()) {
            return back()->with('error', __('academic_calendar.department_assessment_calendar_cannot_delete_closed'));
        }

        $departmentAssessmentCalendar->delete();

        return back()->with('success', __('academic_calendar.department_assessment_calendar_deleted'));
    }

    public function restore(InstitutionDepartment $department, string $departmentAssessmentCalendar): RedirectResponse
    {
        $record = DepartmentAssessmentCalendar::onlyTrashed()
            ->where('institution_department_id', $department->id)
            ->findOrFail($departmentAssessmentCalendar);

        $this->authorize('restore', $record);
        $record->restore();

        $globalCalendar = $record->assessmentCalendar;

        if ($globalCalendar instanceof AssessmentCalendar) {
            app(DepartmentCalendarSyncService::class)->clampToGlobalWindow($globalCalendar);
        }

        return back()->with('success', __('academic_calendar.department_assessment_calendar_restored'));
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributesFrom(array $validated, ?int $userId): array
    {
        return [
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'first_notification_days_before' => $validated['first_notification_days_before'] ?? null,
            'second_notification_days_before' => $validated['second_notification_days_before'] ?? null,
            'due_notification_days_before' => $validated['due_notification_days_before'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'updated_by' => $userId,
        ];
    }

    /**
     * @param  Collection<int|string, string>  $modeNames
     * @return array<string, mixed>
     */
    private function row(AssessmentCalendar $calendar, ?DepartmentAssessmentCalendar $departmentCalendar, Collection $modeNames): array
    {
        $today = now()->startOfDay();
        $globalStart = Carbon::parse($calendar->start_date)->startOfDay();
        $globalEnd = Carbon::parse($calendar->end_date)->startOfDay();
        $activeDepartmentCalendar = $departmentCalendar !== null && ! $departmentCalendar->trashed() ? $departmentCalendar : null;
        $start = $activeDepartmentCalendar ? Carbon::parse($activeDepartmentCalendar->start_date)->startOfDay() : $globalStart;
        $end = $activeDepartmentCalendar ? Carbon::parse($activeDepartmentCalendar->end_date)->startOfDay() : $globalEnd;

        $status = match (true) {
            $today->lt($start) => AssessmentWindowStatusEnum::NotOpen,
            $today->lte($end) => AssessmentWindowStatusEnum::Open,
            default => AssessmentWindowStatusEnum::Closed,
        };

        $academicCalendar = $calendar->academicCalendar;
        $type = $academicCalendar?->type instanceof BackedEnum ? $academicCalendar->type->value : (string) ($academicCalendar?->type ?? '');

        return [
            'globalCalendarId' => (int) $calendar->id,
            'assessmentTypeName' => (string) ($calendar->assessmentType?->name ?? ''),
            'modesOfStudy' => collect($calendar->assessmentType?->modes_of_study ?? [])
                ->map(fn ($modeId): string => (string) ($modeNames->get((int) $modeId) ?? ''))
                ->filter()
                ->values()
                ->all(),
            'academicCalendarLabel' => trim(($academicCalendar?->calendar_year ?? '').' · '.ucfirst($type)),
            'globalStartDate' => $globalStart->toDateString(),
            'globalEndDate' => $globalEnd->toDateString(),
            'globalClosed' => $today->gt($globalEnd),
            'defaultNotificationDays' => [
                'first' => $calendar->daysBeforeFor(MissingMarksNotificationTierEnum::First),
                'second' => $calendar->daysBeforeFor(MissingMarksNotificationTierEnum::Second),
                'due' => $calendar->daysBeforeFor(MissingMarksNotificationTierEnum::Due),
            ],
            'departmentCalendar' => $departmentCalendar === null ? null : [
                'id' => (int) $departmentCalendar->id,
                'startDate' => Carbon::parse($departmentCalendar->start_date)->toDateString(),
                'endDate' => Carbon::parse($departmentCalendar->end_date)->toDateString(),
                'firstNotificationDaysBefore' => $departmentCalendar->first_notification_days_before,
                'secondNotificationDaysBefore' => $departmentCalendar->second_notification_days_before,
                'dueNotificationDaysBefore' => $departmentCalendar->due_notification_days_before,
                'notes' => $departmentCalendar->notes,
                'trashed' => $departmentCalendar->trashed(),
                'started' => $today->gte(Carbon::parse($departmentCalendar->start_date)->startOfDay()),
                'closed' => $today->gt(Carbon::parse($departmentCalendar->end_date)->startOfDay()),
            ],
            'effectiveStartDate' => $start->toDateString(),
            'effectiveEndDate' => $end->toDateString(),
            'status' => $status->value,
            'statusLabel' => $status->label(),
        ];
    }
}
