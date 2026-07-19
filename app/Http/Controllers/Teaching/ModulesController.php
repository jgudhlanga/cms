<?php

namespace App\Http\Controllers\Teaching;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Resources\AcademicCalendars\AcademicCalendarResource;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Services\Lecturer\LecturerTeachingListService;
use App\Support\AcademicCalendars\AcademicCalendarPeriodResolver;
use Inertia\Inertia;
use Inertia\Response;

class ModulesController extends Controller
{
    public function __construct(
        private readonly LecturerTeachingListService $teachingListService,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewLecturerModules');

        $academicCalendar = Helper::resolveAcademicCalendar();

        return Inertia::render('teaching/modules/Index', [
            'modules' => $this->teachingListService->modulesFor(auth()->user()),
            'academicCalendar' => AcademicCalendarResource::make($academicCalendar),
            'academicContextSubtitle' => $this->academicContextSubtitle($academicCalendar),
            'canEnterMarks' => $this->canEnterMarks(),
        ]);
    }

    public function show(CourseSyllabusModule $courseSyllabusModule): Response
    {
        $this->authorize('viewLecturerModules');

        $detail = $this->teachingListService->moduleDetailFor(auth()->user(), $courseSyllabusModule);
        abort_if($detail === null, 403);

        $academicCalendar = Helper::resolveAcademicCalendar();

        return Inertia::render('teaching/modules/Show', [
            'moduleDetail' => $detail,
            'academicCalendar' => AcademicCalendarResource::make($academicCalendar),
            'academicContextSubtitle' => $this->academicContextSubtitle($academicCalendar),
            'canEnterMarks' => $this->canEnterMarks(),
            'canExportCourseWork' => auth()->user()?->can('export', CourseWorkMark::class) ?? false,
            'canImportCourseWork' => auth()->user()?->can('import', CourseWorkMark::class) ?? false,
        ]);
    }

    private function canEnterMarks(): bool
    {
        $user = auth()->user();

        return ($user?->can('viewAny', CourseWorkMark::class) ?? false)
            || ($user?->can('create', CourseWorkMark::class) ?? false)
            || ($user?->can('update', CourseWorkMark::class) ?? false);
    }

    private function academicContextSubtitle(mixed $academicCalendar): string
    {
        return __('dashboard.academic_context_subtitle', [
            'calendar_year' => $academicCalendar->calendar_year,
            'period' => AcademicCalendarPeriodResolver::displayPeriodLabel($academicCalendar),
            'date_range' => AcademicCalendarPeriodResolver::dateRangeLabel($academicCalendar),
        ]);
    }
}
