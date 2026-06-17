<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\DropdownHelper;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Resources\AcademicCalendars\AcademicCalendarResource;
use App\Http\Resources\Enrolments\DailyDistributionResource;
use App\Http\Resources\Enrolments\DepartmentDistributionResource;
use App\Http\Resources\Enrolments\LevelDistributionResource;
use App\Http\Resources\Institution\IntakePeriodResource;
use App\Services\ApplicationMetricsService;
use App\Services\Dashboard\DashboardModuleService;
use App\Services\Dashboard\HostelDashboardMetricsService;
use App\Support\AcademicCalendars\AcademicCalendarPeriodResolver;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(protected ApplicationMetricsService $metricsService) {}

    public function __invoke()
    {
        $this->authorize('viewDashboard');

        $user = auth()->user();
        $dashboardModuleService = app(DashboardModuleService::class);

        $intakePeriodList = DropdownHelper::getIntakePeriods();
        $intakePeriods = IntakePeriodResource::collection($intakePeriodList);
        $intakePeriod = IntakePeriodResource::make(Helper::resolveIntakePeriod());
        $academicCalendar = Helper::resolveAcademicCalendar();
        $academicCalendars = AcademicCalendarResource::collection(
            DropdownHelper::getSemestersForCalendarYear($academicCalendar->calendar_year),
        );
        $academicContextSubtitle = __('dashboard.academic_context_subtitle', [
            'calendar_year' => $academicCalendar->calendar_year,
            'period' => AcademicCalendarPeriodResolver::displayPeriodLabel($academicCalendar),
            'date_range' => AcademicCalendarPeriodResolver::dateRangeLabel($academicCalendar),
        ]);
        $departmentDistribution = DepartmentDistributionResource::collection($this->metricsService->applicationsByDepartment());
        $levelDistribution = LevelDistributionResource::collection($this->metricsService->applicationsByLevel());
        $dailyDistribution = DailyDistributionResource::collection($this->metricsService->getDailyCountStats());
        $enrolmentSummary = $this->metricsService->enrolmentSummaryMetrics();
        $visibleTabs = $dashboardModuleService->visibleTabsFor($user);

        return Inertia::render('dashboard/Index', [
            'departmentDistribution' => $departmentDistribution,
            'levelDistribution' => $levelDistribution,
            'dailyDistribution' => $dailyDistribution,
            'enrolmentSummary' => $enrolmentSummary,
            'academicCalendar' => AcademicCalendarResource::make($academicCalendar),
            'academicCalendars' => $academicCalendars,
            'academicContextSubtitle' => $academicContextSubtitle,
            'hostelDashboard' => in_array('hostel', $visibleTabs, true)
                ? app(HostelDashboardMetricsService::class)->build()
                : null,
            'intakePeriod' => $intakePeriod,
            'intakePeriods' => $intakePeriods,
            'visibleTabs' => $visibleTabs,
            'dashboardTitle' => $dashboardModuleService->dashboardTitleFor($user),
            'moduleEnabled' => $dashboardModuleService->isEnabled(),
        ]);
    }
}
