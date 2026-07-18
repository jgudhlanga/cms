<?php

namespace App\Http\Controllers\Teaching;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Resources\AcademicCalendars\AcademicCalendarResource;
use App\Services\Lecturer\LecturerTeachingListService;
use App\Support\AcademicCalendars\AcademicCalendarPeriodResolver;
use Inertia\Inertia;
use Inertia\Response;

class ModulesController extends Controller
{
    public function index(LecturerTeachingListService $teachingListService): Response
    {
        $this->authorize('viewLecturerModules');

        $academicCalendar = Helper::resolveAcademicCalendar();

        return Inertia::render('teaching/modules/Index', [
            'modules' => $teachingListService->modulesFor(auth()->user()),
            'academicCalendar' => AcademicCalendarResource::make($academicCalendar),
            'academicContextSubtitle' => __('dashboard.academic_context_subtitle', [
                'calendar_year' => $academicCalendar->calendar_year,
                'period' => AcademicCalendarPeriodResolver::displayPeriodLabel($academicCalendar),
                'date_range' => AcademicCalendarPeriodResolver::dateRangeLabel($academicCalendar),
            ]),
        ]);
    }
}
