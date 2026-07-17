<?php

namespace App\Http\Controllers\Lecturer;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Resources\AcademicCalendars\AcademicCalendarResource;
use App\Services\Dashboard\LecturerDashboardMetricsService;
use App\Support\AcademicCalendars\AcademicCalendarPeriodResolver;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(LecturerDashboardMetricsService $metricsService): Response
    {
        $this->authorize('viewLecturerDashboard');

        $user = auth()->user();
        $academicCalendar = Helper::resolveAcademicCalendar();

        return Inertia::render('lecturer/dashboard/Index', [
            'dashboard' => $metricsService->build($user),
            'academicCalendar' => AcademicCalendarResource::make($academicCalendar),
            'academicContextSubtitle' => __('dashboard.academic_context_subtitle', [
                'calendar_year' => $academicCalendar->calendar_year,
                'period' => AcademicCalendarPeriodResolver::displayPeriodLabel($academicCalendar),
                'date_range' => AcademicCalendarPeriodResolver::dateRangeLabel($academicCalendar),
            ]),
            'dashboardTitle' => __('dashboard.lecturer_dashboard_title'),
        ]);
    }
}
