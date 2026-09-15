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
use App\Models\Examinations\ExaminationResult;
use App\Models\Users\User;
use App\Services\ApplicationMetricsService;
use App\Services\Dashboard\AcademicDashboardMetricsService;
use App\Services\Dashboard\DashboardModuleService;
use App\Services\Dashboard\FinanceCashFlowMetricsService;
use App\Services\Dashboard\HostelDashboardMetricsService;
use App\Services\Dashboard\LecturerDashboardMetricsService;
use App\Services\Dashboard\OverviewDashboardMetricsService;
use App\Services\Dashboard\StaffDashboardMetricsService;
use App\Services\Examinations\ExaminationDashboardService;
use App\Support\AcademicCalendars\AcademicCalendarPeriodResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Props filled from one examination dashboard payload.
     */
    private const array EXAMINATION_PROPS = [
        'filters',
        'filterOptions',
        'statusCounts',
        'statusLabels',
        'chartLabels',
        'totalCandidates',
        'passRate',
        'onlineViewedCount',
        'onlineViewedRate',
        'comparison',
    ];

    public function __construct(protected ApplicationMetricsService $metricsService) {}

    public function __invoke(Request $request): Response
    {
        $this->authorize('viewDashboard');

        $user = auth()->user();
        $dashboardModuleService = app(DashboardModuleService::class);

        $intakePeriodList = DropdownHelper::getIntakePeriods();
        $intakePeriods = IntakePeriodResource::collection($intakePeriodList);
        $intakePeriod = IntakePeriodResource::make(Helper::resolveIntakePeriod());
        $academicCalendar = Helper::resolveAcademicCalendar();
        $academicContextSubtitle = __('dashboard.academic_context_subtitle', [
            'calendar_year' => $academicCalendar->calendar_year,
            'period' => AcademicCalendarPeriodResolver::displayPeriodLabel($academicCalendar),
            'date_range' => AcademicCalendarPeriodResolver::dateRangeLabel($academicCalendar),
        ]);
        $visibleTabs = $dashboardModuleService->visibleTabsFor($user);
        $activeTab = $this->resolveActiveTab($request, $visibleTabs);

        return Inertia::render('dashboard/Index', [
            ...$this->tabProps($request, $user, $visibleTabs, $activeTab),
            'academicCalendar' => AcademicCalendarResource::make($academicCalendar),
            'academicContextSubtitle' => $academicContextSubtitle,
            'intakePeriod' => $intakePeriod,
            'intakePeriods' => $intakePeriods,
            'visibleTabs' => $visibleTabs,
            'activeTab' => $activeTab,
            'dashboardTitle' => $dashboardModuleService->dashboardTitleFor($user),
            'moduleEnabled' => $dashboardModuleService->isEnabled(),
        ]);
    }

    /**
     * The tab opened by ?tab=, falling back to the first tab the user can see.
     *
     * @param  list<string>  $visibleTabs
     */
    private function resolveActiveTab(Request $request, array $visibleTabs): ?string
    {
        $requested = $request->query('tab');

        return is_string($requested) && in_array($requested, $visibleTabs, true)
            ? $requested
            : ($visibleTabs[0] ?? null);
    }

    /**
     * Metrics grouped by the tab that shows them. Tabs the user cannot see are null. Only the active tab is
     * computed with the page; other visible tabs are optional props that a partial reload fetches the first
     * time the tab is opened.
     *
     * @param  list<string>  $visibleTabs
     * @return array<string, mixed>
     */
    private function tabProps(Request $request, User $user, array $visibleTabs, ?string $activeTab): array
    {
        $academicTabVisible = in_array('academic', $visibleTabs, true);

        $examinationDashboard = null;
        $examinationProp = function (string $key) use ($request, $user, $visibleTabs, &$examinationDashboard): mixed {
            $examinationDashboard ??= $this->examinationDashboard($request, $user, $visibleTabs) ?? [];

            return $examinationDashboard[$key] ?? null;
        };

        $tabs = [
            'overview' => [
                'overviewDashboard' => fn () => in_array('overview', $visibleTabs, true)
                    ? app(OverviewDashboardMetricsService::class)->build($visibleTabs)
                    : null,
            ],
            'academic' => [
                'academicDashboard' => fn () => $academicTabVisible
                    && ($user->can('viewAny:dashboards') || $user->can('view-academic:dashboards'))
                    ? app(AcademicDashboardMetricsService::class)->build()
                    : null,
                'teachingDashboard' => fn () => $academicTabVisible && $user->can('view:lecturer-dashboard')
                    ? app(LecturerDashboardMetricsService::class)->build($user)
                    : null,
            ],
            'enrolments' => [
                'departmentDistribution' => fn () => DepartmentDistributionResource::collection($this->metricsService->applicationsByDepartment()),
                'levelDistribution' => fn () => LevelDistributionResource::collection($this->metricsService->applicationsByLevel()),
                'dailyDistribution' => fn () => DailyDistributionResource::collection($this->metricsService->getDailyCountStats()),
                'enrolmentSummary' => fn () => $this->metricsService->enrolmentSummaryMetrics(),
            ],
            'staff' => [
                'staffDashboard' => fn () => in_array('staff', $visibleTabs, true)
                    ? app(StaffDashboardMetricsService::class)->build()
                    : null,
            ],
            'finance' => [
                'financeDashboard' => fn () => in_array('finance', $visibleTabs, true)
                    ? app(FinanceCashFlowMetricsService::class)->build()
                    : null,
            ],
            'hostel' => [
                'hostelDashboard' => fn () => in_array('hostel', $visibleTabs, true)
                    ? app(HostelDashboardMetricsService::class)->build()
                    : null,
            ],
            'examinations' => collect(self::EXAMINATION_PROPS)
                ->mapWithKeys(fn (string $key): array => [$key => fn () => $examinationProp($key)])
                ->all(),
        ];

        $props = [];

        foreach ($tabs as $tab => $resolvers) {
            foreach ($resolvers as $key => $resolver) {
                $props[$key] = match (true) {
                    ! in_array($tab, $visibleTabs, true) => null,
                    $tab === $activeTab => $resolver,
                    default => Inertia::optional($resolver),
                };
            }
        }

        return $props;
    }

    /**
     * @param  list<string>  $visibleTabs
     * @return array<string, mixed>|null
     */
    private function examinationDashboard(Request $request, User $user, array $visibleTabs): ?array
    {
        $canLoadExaminations = in_array('examinations', $visibleTabs, true) && (
            $user->can('viewAny:dashboards')
            || $user->can('view-examinations:dashboards')
            || $user->can('viewAny', ExaminationResult::class)
        );

        return $canLoadExaminations
            ? app(ExaminationDashboardService::class)->pagePayload($this->examinationFilters($request))
            : null;
    }

    /**
     * @return array{
     *     session?: string|null,
     *     discipline?: string|null,
     *     subject_code?: string|null,
     *     compare_session?: string|null,
     * }
     */
    private function examinationFilters(Request $request): array
    {
        $validated = $request->validate([
            'session' => ['nullable', 'string', 'max:100'],
            'discipline' => ['nullable', 'string', 'max:255'],
            'subject_code' => ['nullable', 'string', 'max:100'],
            'compare_session' => ['nullable', 'string', 'max:100'],
        ]);

        return $validated;
    }
}
