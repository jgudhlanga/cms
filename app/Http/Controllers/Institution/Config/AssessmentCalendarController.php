<?php

namespace App\Http\Controllers\Institution\Config;

use App\DTO\Institution\AssessmentCalendarDto;
use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Filters\Institution\AssessmentCalendarFilter;
use App\Http\Requests\Institution\AssessmentCalendarRequest;
use App\Http\Resources\AcademicCalendars\AcademicCalendarResource;
use App\Http\Resources\Institution\AssessmentCalendarResource;
use App\Http\Resources\Institution\AssessmentTypeResource;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\AssessmentType;
use App\Repositories\Institution\interface\IAssessmentCalendarRepository;
use App\Models\Institution\AssessmentCalendar\DepartmentAssessmentCalendar;
use App\Notifications\Assessments\GlobalAssessmentCalendarChangedNotification;
use App\Services\Assessments\DepartmentCalendarSyncService;
use App\Support\Institution\DepartmentLeadershipResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class AssessmentCalendarController extends Controller
{
    public function __construct(protected IAssessmentCalendarRepository $repository) {}

    public function index(AssessmentType $assessmentType, AssessmentCalendarFilter $filters)
    {
        $this->authorize('viewAny', AssessmentCalendar::class);

        return Inertia::render('institution/dropdowns/assessment-types/calendars/Index', [
            'assessmentType' => new AssessmentTypeResource($assessmentType),
            'assessmentCalendars' => AssessmentCalendarResource::collection(
                $this->repository->allFilter($assessmentType->id, ['*'], $filters)
            ),
            'academicCalendars' => AcademicCalendarResource::collection(
                AcademicCalendar::query()
                    ->where('calendar_year', (string) now()->year)
                    ->orderByRaw("CASE type WHEN 'semester' THEN 1 WHEN 'term' THEN 2 WHEN 'abma' THEN 3 ELSE 4 END")
                    ->orderBy('opening_date')
                    ->get()
            ),
            'calendarTypes' => collect([
                AcademicCalendarTypeEnum::SEMESTER,
                AcademicCalendarTypeEnum::TERM,
                AcademicCalendarTypeEnum::ABMA,
            ])->map(fn (AcademicCalendarTypeEnum $type) => [
                'value' => $type->value,
                'label' => ucfirst($type->value),
            ])->values(),
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashedForAssessmentType($assessmentType->id)->count(),
            'existingAssessmentCalendars' => AssessmentCalendarResource::collection(
                AssessmentCalendar::query()
                    ->with('academicCalendar')
                    ->where('assessment_type_id', $assessmentType->id)
                    ->whereHas('academicCalendar', fn ($query) => $query->where('calendar_year', (string) now()->year))
                    ->get()
            ),
            'assessmentCalendarLimits' => [
                'semester' => AcademicCalendarTypeEnum::SEMESTER->maxAssessmentCalendarsPerYear(),
                'term' => AcademicCalendarTypeEnum::TERM->maxAssessmentCalendarsPerYear(),
                'abma' => AcademicCalendarTypeEnum::ABMA->maxAssessmentCalendarsPerYear(),
            ],
        ]);
    }

    public function store(AssessmentCalendarRequest $request, AssessmentType $assessmentType)
    {
        $this->authorize('create', AssessmentCalendar::class);
        $this->repository->create(AssessmentCalendarDto::fromAssessmentCalendarRequest($request, $assessmentType));
    }

    public function update(
        AssessmentCalendarRequest $request,
        AssessmentType $assessmentType,
        AssessmentCalendar $calendar,
    ) {
        $this->authorize('update', $calendar);
        $this->authorizeClosedCalendarChange($calendar);

        [$updated, $changedDepartmentCalendars] = DB::transaction(function () use ($request, $assessmentType, $calendar): array {
            $updated = $this->repository->update(
                $calendar,
                AssessmentCalendarDto::fromAssessmentCalendarRequest($request, $assessmentType)
            );

            return [$updated, app(DepartmentCalendarSyncService::class)->clampToGlobalWindow($updated)];
        });

        $this->notifyHeadsOfMovedDepartments($updated, $changedDepartmentCalendars);
    }

    /**
     * @param  list<DepartmentAssessmentCalendar>  $changedDepartmentCalendars
     */
    private function notifyHeadsOfMovedDepartments(AssessmentCalendar $calendar, array $changedDepartmentCalendars): void
    {
        $calendar->loadMissing('assessmentType');
        $leadership = app(DepartmentLeadershipResolver::class);

        foreach ($changedDepartmentCalendars as $departmentCalendar) {
            $departmentCalendar->loadMissing('institutionDepartment.department');
            $heads = $leadership->headsOfDepartment((int) $departmentCalendar->institution_department_id);

            if ($heads->isEmpty()) {
                continue;
            }

            Notification::send($heads, new GlobalAssessmentCalendarChangedNotification(
                (string) ($calendar->assessmentType?->name ?? ''),
                (string) ($departmentCalendar->institutionDepartment?->department?->name ?? ''),
                (int) $departmentCalendar->institution_department_id,
                $calendar->start_date->format('d M Y'),
                $calendar->end_date->format('d M Y'),
                $departmentCalendar->start_date->format('d M Y'),
                $departmentCalendar->end_date->format('d M Y'),
            ));
        }
    }

    public function destroy(AssessmentType $assessmentType, AssessmentCalendar $calendar)
    {
        $this->authorize('delete', $calendar);
        $this->authorizeClosedCalendarChange($calendar);
        $this->repository->delete($calendar);
    }

    public function restore(AssessmentType $assessmentType, string $calendar)
    {
        $record = $this->repository->findTrashed($calendar);
        abort_unless(
            $record instanceof AssessmentCalendar && (int) $record->assessment_type_id === (int) $assessmentType->id,
            404,
        );
        $this->authorize('restore', $record);
        $this->repository->restore($record);
    }

    public function forceDelete(AssessmentType $assessmentType, AssessmentCalendar $calendar)
    {
        $this->authorize('forceDelete', $calendar);
        $this->authorizeClosedCalendarChange($calendar);
        $this->repository->delete($calendar, true);
    }

    /**
     * A closed window locks capture; editing or removing it reopens capture for every class, so
     * only holders of updateClosed may do it (the change itself is recorded by the activity log).
     */
    private function authorizeClosedCalendarChange(AssessmentCalendar $calendar): void
    {
        if ($calendar->end_date !== null && $calendar->end_date->copy()->endOfDay()->isPast()) {
            $this->authorize('updateClosed', $calendar);
        }
    }
}
