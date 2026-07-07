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
        $this->repository->update(
            $calendar,
            AssessmentCalendarDto::fromAssessmentCalendarRequest($request, $assessmentType)
        );
    }

    public function destroy(AssessmentType $assessmentType, AssessmentCalendar $calendar)
    {
        $this->authorize('delete', $calendar);
        $this->repository->delete($calendar);
    }

    public function restore(AssessmentType $assessmentType, string $calendar)
    {
        $record = $this->repository->findTrashed($calendar);
        $this->authorize('restore', $record);
        $this->repository->restore($record);
    }

    public function forceDelete(AssessmentType $assessmentType, AssessmentCalendar $calendar)
    {
        $this->authorize('forceDelete', $calendar);
        $this->repository->delete($calendar, true);
    }
}
