<?php

namespace App\Services\Dashboard;

use App\Enums\HMS\HostelQueryPriorityEnum;
use App\Enums\HMS\HostelQueryStatusEnum;
use App\Helpers\Helper;
use App\Models\HMS\HostelQuery;
use App\Services\ApplicationMetricsService;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OverviewDashboardMetricsService
{
    protected bool $isDepartmentUser = false;

    /** @var list<int> */
    protected array $userDepartments = [];

    public function __construct(
        private readonly ApplicationMetricsService $applicationMetricsService,
        private readonly AcademicDashboardMetricsService $academicDashboardMetricsService,
        private readonly HostelDashboardMetricsService $hostelDashboardMetricsService,
        private readonly StaffDashboardMetricsService $staffDashboardMetricsService,
    ) {
        $this->isDepartmentUser = Helper::isDepartmentUser();
        $this->userDepartments = Helper::resolveUserDepartments() ?? [];
    }

    /**
     * @return array{
     *     summary: array<string, float|int|string|null>,
     *     enrolmentFunnel: array<string, int>,
     *     academicSnapshot: array<string, mixed>,
     *     quickInsights: list<array{key: string, message: string}>,
     *     enrolmentByDepartment: list<array<string, mixed>>,
     *     priorityAlerts: list<array<string, mixed>>
     * }
     */
    /**
     * @param  list<string>  $visibleTabs
     */
    public function build(array $visibleTabs = []): array
    {
        $hasAcademic = $this->hasTab($visibleTabs, 'academic');
        $hasHostel = $this->hasTab($visibleTabs, 'hostel');
        $hasStaff = $this->hasTab($visibleTabs, 'staff');
        $hasEnrolments = $this->hasTab($visibleTabs, 'enrolments');

        $intakePeriod = Helper::resolveIntakePeriod();
        $enrolmentSummary = $hasEnrolments
            ? $this->applicationMetricsService->enrolmentSummaryMetrics()
            : $this->emptyEnrolmentSummary();
        $academicDashboard = $hasAcademic ? $this->academicDashboardMetricsService->build() : [];
        $hostelDashboard = $hasHostel ? $this->hostelDashboardMetricsService->build() : [];
        $staffDashboard = $hasStaff ? $this->staffDashboardMetricsService->build() : [];
        $hostelSummary = $hasHostel ? $this->hostelSummary($hostelDashboard) : null;

        return [
            'summary' => $this->summary(
                $academicDashboard,
                $hostelSummary,
                $staffDashboard,
                $hasAcademic,
                $hasStaff,
            ),
            'enrolmentFunnel' => $hasEnrolments ? $this->enrolmentFunnel($enrolmentSummary) : $this->emptyEnrolmentFunnel(),
            'academicSnapshot' => $hasAcademic ? $this->academicSnapshot($academicDashboard) : $this->emptyAcademicSnapshot(),
            'quickInsights' => $this->quickInsights($academicDashboard, $staffDashboard, $hasAcademic, $hasStaff),
            'enrolmentByDepartment' => $hasEnrolments ? $this->enrolmentByDepartment($intakePeriod?->id) : [],
            'priorityAlerts' => $this->priorityAlerts(
                $enrolmentSummary,
                $academicDashboard,
                $hostelDashboard,
                $hasAcademic,
                $hasHostel,
                $hasEnrolments,
            ),
        ];
    }

    /**
     * @param  list<string>  $visibleTabs
     */
    private function hasTab(array $visibleTabs, string $tab): bool
    {
        return in_array($tab, $visibleTabs, true);
    }

    /**
     * @param  array<string, mixed>  $academicDashboard
     * @param  array<string, mixed>  $staffDashboard
     * @return array<string, float|int|string|null>
     */
    private function summary(
        array $academicDashboard,
        ?array $hostelSummary,
        array $staffDashboard,
        bool $hasAcademic,
        bool $hasStaff,
    ): array {
        $courseWorkStatus = $academicDashboard['courseWorkStatus'] ?? [];
        $academicSummary = $academicDashboard['summary'] ?? [];
        $staffSummary = $staffDashboard['summary'] ?? [];

        return [
            'passRate' => $hasAcademic ? ($academicSummary['passRate'] ?? null) : null,
            'passRateSubtext' => $hasAcademic ? $this->passRateSubtext($academicSummary) : null,
            'markCompletionRate' => $hasAcademic ? ($courseWorkStatus['completeRate'] ?? null) : null,
            'markCompletionSubtext' => $hasAcademic ? $this->markCompletionSubtext($courseWorkStatus) : null,
            'atRiskStudents' => $hasAcademic ? $this->academicDashboardMetricsService->atRiskStudentCount() : null,
            'atRiskSubtext' => $hasAcademic ? __('dashboard.overview_at_risk_subtext') : null,
            'hostelOccupancyRate' => $hostelSummary['occupancyRate'] ?? null,
            'hostelAvailableBeds' => $hostelSummary['availableBeds'] ?? null,
            'hostelSubtext' => $hostelSummary !== null
                ? __('dashboard.overview_hostel_beds_available', ['count' => $hostelSummary['availableBeds']])
                : null,
            'totalStaff' => $hasStaff ? ($staffSummary['totalStaff'] ?? 0) : 0,
            'totalStaffSubtext' => $hasStaff
                ? __('dashboard.staff_academic_admin_subtext', [
                    'academic' => $staffSummary['academicCount'] ?? 0,
                    'admin' => $staffSummary['adminCount'] ?? 0,
                ])
                : null,
        ];
    }

    /**
     * @param  array{applications: int, offersMade: int, confirmed: int, waitlisted: int, provisional: int, failedRejected: int}  $enrolmentSummary
     * @return array{applications: int, offersMade: int, confirmed: int, waitlisted: int, provisional: int, acceptanceRate: int|null, yieldRate: int|null}
     */
    private function enrolmentFunnel(array $enrolmentSummary): array
    {
        $applications = $enrolmentSummary['applications'];
        $offersMade = $enrolmentSummary['offersMade'];
        $confirmed = $enrolmentSummary['confirmed'];

        return [
            'applications' => $applications,
            'offersMade' => $offersMade,
            'confirmed' => $confirmed,
            'waitlisted' => $enrolmentSummary['waitlisted'],
            'provisional' => $enrolmentSummary['provisional'],
            'acceptanceRate' => $applications > 0 ? (int) round(($offersMade / $applications) * 100) : null,
            'yieldRate' => $offersMade > 0 ? (int) round(($confirmed / $offersMade) * 100) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $academicDashboard
     * @return array{gradeSegments: list<array<string, mixed>>, topFailureHotspots: list<array<string, mixed>>, markCompletion: array<string, mixed>}
     */
    private function academicSnapshot(array $academicDashboard): array
    {
        $hotspots = $academicDashboard['moduleFailureHotspots'] ?? [];

        return [
            'gradeSegments' => $academicDashboard['gradeDistribution']['segments'] ?? [],
            'topFailureHotspots' => array_slice($hotspots, 0, 3),
            'markCompletion' => $academicDashboard['courseWorkStatus'],
        ];
    }

    /**
     * @param  array<string, mixed>  $academicDashboard
     * @param  array<string, mixed>  $staffDashboard
     * @return list<array{key: string, message: string}>
     */
    private function quickInsights(
        array $academicDashboard,
        array $staffDashboard,
        bool $hasAcademic,
        bool $hasStaff,
    ): array {
        $insights = [];

        if (! $hasAcademic && ! $hasStaff) {
            return [];
        }

        $attachmentStatus = $hasAcademic ? ($academicDashboard['attachmentStatus'] ?? null) : null;

        if (is_array($attachmentStatus) && ($attachmentStatus['total'] ?? 0) > 0) {
            $insights[] = [
                'key' => 'attachment',
                'message' => __('dashboard.overview_insight_attachment', [
                    'placed' => $attachmentStatus['placed'],
                    'total' => $attachmentStatus['total'],
                    'year' => $attachmentStatus['calendarYear'],
                ]),
            ];
        }

        $passRates = $hasAcademic ? ($academicDashboard['passRateByDepartment'] ?? []) : [];

        if ($passRates !== []) {
            $worst = collect($passRates)->sortBy('passRate')->first();

            if (is_array($worst)) {
                $insights[] = [
                    'key' => 'lowest_pass_rate',
                    'message' => __('dashboard.overview_insight_lowest_pass_rate', [
                        'department' => $worst['departmentName'],
                        'rate' => $worst['passRate'],
                    ]),
                ];
            }
        }

        $ratios = $hasStaff ? ($staffDashboard['lecturerRatios'] ?? []) : [];

        if ($ratios !== []) {
            $highest = collect($ratios)
                ->filter(fn (array $row): bool => $row['ratio'] !== null)
                ->sortByDesc('ratio')
                ->first();

            if (is_array($highest)) {
                $insights[] = [
                    'key' => 'lecturer_ratio',
                    'message' => __('dashboard.overview_insight_lecturer_ratio', [
                        'department' => $highest['departmentName'],
                        'ratio' => $highest['ratioLabel'],
                    ]),
                ];
            }
        }

        return array_slice($insights, 0, 3);
    }

    /**
     * @param  array{passRate: int|null, failRate: int|null, distinctionRate: int|null}  $academicSummary
     */
    private function passRateSubtext(array $academicSummary): ?string
    {
        if ($academicSummary['distinctionRate'] !== null) {
            return __('dashboard.overview_pass_rate_distinction_subtext', [
                'rate' => $academicSummary['distinctionRate'],
            ]);
        }

        if ($academicSummary['failRate'] !== null) {
            return __('dashboard.overview_pass_rate_fail_subtext', [
                'rate' => $academicSummary['failRate'],
            ]);
        }

        return null;
    }

    /**
     * @param  array{incompleteCount: int, outstandingCount: int}  $courseWorkStatus
     */
    private function markCompletionSubtext(array $courseWorkStatus): ?string
    {
        if ($courseWorkStatus['incompleteCount'] > 0) {
            return __('dashboard.overview_mark_completion_incomplete', [
                'count' => $courseWorkStatus['incompleteCount'],
            ]);
        }

        if ($courseWorkStatus['outstandingCount'] > 0) {
            return __('dashboard.overview_mark_completion_outstanding', [
                'count' => $courseWorkStatus['outstandingCount'],
            ]);
        }

        return __('dashboard.overview_mark_completion_complete');
    }

    /**
     * @return list<array{departmentId: int, departmentName: string, count: int, barPercent: int}>
     */
    private function enrolmentByDepartment(?int $intakePeriodId): array
    {
        if ($intakePeriodId === null || ($this->isDepartmentUser && $this->userDepartments === [])) {
            return [];
        }

        $rows = $this->confirmedStudentsByDepartment($intakePeriodId)
            ->map(fn (object $row): array => [
                'departmentId' => (int) $row->department_id,
                'departmentName' => (string) $row->department_name,
                'count' => (int) $row->student_count,
                'barPercent' => 0,
            ])
            ->values()
            ->all();

        $maxCount = collect($rows)->max('count') ?? 0;

        if ($maxCount > 0) {
            $rows = array_map(function (array $row) use ($maxCount): array {
                $row['barPercent'] = (int) round(($row['count'] / $maxCount) * 100);

                return $row;
            }, $rows);
        }

        usort($rows, fn (array $left, array $right): int => $right['count'] <=> $left['count']);

        return $rows;
    }

    /**
     * @param  array{applications: int, offersMade: int, confirmed: int, waitlisted: int, provisional: int, failedRejected: int}  $enrolmentSummary
     * @param  array<string, mixed>  $academicDashboard
     * @param  array<string, mixed>  $hostelDashboard
     * @return list<array{severity: string, message: string, updatedAt: string|null}>
     */
    private function priorityAlerts(
        array $enrolmentSummary,
        array $academicDashboard,
        array $hostelDashboard,
        bool $hasAcademic,
        bool $hasHostel,
        bool $hasEnrolments,
    ): array {
        $alerts = [];

        if (! $hasAcademic && ! $hasHostel && ! $hasEnrolments) {
            return [];
        }

        $topHotspot = $hasAcademic ? ($academicDashboard['moduleFailureHotspots'][0] ?? null) : null;

        if (is_array($topHotspot) && ($topHotspot['rate'] ?? 0) > 0) {
            $alerts[] = [
                'severity' => ($topHotspot['rate'] >= 25) ? 'critical' : 'warning',
                'message' => __('dashboard.overview_alert_module_failure', [
                    'module' => $topHotspot['moduleName'],
                    'rate' => $topHotspot['rate'],
                ]),
                'updatedAt' => null,
            ];
        }

        $courseWorkStatus = $hasAcademic ? ($academicDashboard['courseWorkStatus'] ?? []) : [];
        $topMissingDept = $hasAcademic ? ($academicDashboard['missingMarksByDepartment'][0] ?? null) : null;

        if ($hasAcademic && ($courseWorkStatus['incompleteCount'] ?? 0) > 0) {
            $departmentName = is_array($topMissingDept)
                ? $topMissingDept['departmentName']
                : __('dashboard.academic_unknown_department');

            $alerts[] = [
                'severity' => 'warning',
                'message' => __('dashboard.overview_alert_incomplete_marks', [
                    'count' => $courseWorkStatus['incompleteCount'],
                    'department' => $departmentName,
                ]),
                'updatedAt' => null,
            ];
        }

        if ($hasAcademic && ($courseWorkStatus['outstandingCount'] ?? 0) > 0) {
            $alerts[] = [
                'severity' => 'warning',
                'message' => __('dashboard.overview_alert_outstanding_marks', [
                    'count' => $courseWorkStatus['outstandingCount'],
                ]),
                'updatedAt' => null,
            ];
        }

        $topLecturer = $hasAcademic ? ($academicDashboard['lecturerMarkingStats'][0] ?? null) : null;

        if ($hasAcademic && is_array($topLecturer) && ($topLecturer['incompleteRate'] ?? 0) >= 25) {
            $alerts[] = [
                'severity' => 'warning',
                'message' => __('dashboard.overview_alert_lecturer_incomplete', [
                    'lecturer' => $topLecturer['lecturerName'],
                    'rate' => $topLecturer['incompleteRate'],
                ]),
                'updatedAt' => null,
            ];
        }

        $openStatus = HostelQueryStatusEnum::OPEN->value;
        $inProgressStatus = HostelQueryStatusEnum::IN_PROGRESS->value;
        $highPriority = HostelQueryPriorityEnum::HIGH->value;

        if ($hasHostel) {
            HostelQuery::query()
                ->where('priority', $highPriority)
                ->whereIn('status', [$openStatus, $inProgressStatus])
                ->orderByDesc('updated_at')
                ->limit(2)
                ->get()
                ->each(function (HostelQuery $query) use (&$alerts): void {
                    $message = trim($query->subject.' — '.$query->description, ' —');

                    $alerts[] = [
                        'severity' => 'critical',
                        'message' => $message !== '' ? $message : __('dashboard.overview_alert_hostel_query'),
                        'updatedAt' => $query->updated_at?->toIso8601String(),
                    ];
                });
        }

        $highPriorityQueries = $hasHostel ? (int) ($hostelDashboard['queryStats']['highPriority'] ?? 0) : 0;

        if ($hasHostel && $highPriorityQueries > 0) {
            $alerts[] = [
                'severity' => 'critical',
                'message' => __('dashboard.overview_alert_hostel_high_priority', [
                    'count' => $highPriorityQueries,
                ]),
                'updatedAt' => null,
            ];
        }

        if ($hasEnrolments && $enrolmentSummary['provisional'] > 0) {
            $alerts[] = [
                'severity' => 'warning',
                'message' => __('dashboard.overview_alert_provisional', [
                    'count' => $enrolmentSummary['provisional'],
                ]),
                'updatedAt' => null,
            ];
        }

        if ($hasEnrolments && $enrolmentSummary['waitlisted'] > 0) {
            $alerts[] = [
                'severity' => 'warning',
                'message' => __('dashboard.overview_alert_waitlisted', [
                    'count' => $enrolmentSummary['waitlisted'],
                ]),
                'updatedAt' => null,
            ];
        }

        if ($hasEnrolments && $enrolmentSummary['applications'] > 0) {
            $alerts[] = [
                'severity' => 'info',
                'message' => __('dashboard.overview_alert_applications', [
                    'count' => $enrolmentSummary['applications'],
                ]),
                'updatedAt' => null,
            ];
        }

        $attachmentStatus = $hasAcademic ? ($academicDashboard['attachmentStatus'] ?? null) : null;

        if ($hasAcademic && is_array($attachmentStatus) && ($attachmentStatus['placed'] ?? 0) > 0) {
            $alerts[] = [
                'severity' => 'success',
                'message' => __('dashboard.overview_alert_attachment', [
                    'count' => $attachmentStatus['placed'],
                ]),
                'updatedAt' => null,
            ];
        }

        return $alerts;
    }

    /**
     * @param  array<string, mixed>  $hostelDashboard
     * @return array{occupancyRate: int, availableBeds: int}|null
     */
    /**
     * @return array{applications: int, offersMade: int, confirmed: int, waitlisted: int, provisional: int, failedRejected: int}
     */
    private function emptyEnrolmentSummary(): array
    {
        return [
            'applications' => 0,
            'offersMade' => 0,
            'confirmed' => 0,
            'waitlisted' => 0,
            'provisional' => 0,
            'failedRejected' => 0,
        ];
    }

    /**
     * @return array{applications: int, offersMade: int, confirmed: int, waitlisted: int, provisional: int, acceptanceRate: null, yieldRate: null}
     */
    private function emptyEnrolmentFunnel(): array
    {
        return [
            'applications' => 0,
            'offersMade' => 0,
            'confirmed' => 0,
            'waitlisted' => 0,
            'provisional' => 0,
            'acceptanceRate' => null,
            'yieldRate' => null,
        ];
    }

    /**
     * @return array{gradeSegments: array{}, topFailureHotspots: array{}, markCompletion: array{}}
     */
    private function emptyAcademicSnapshot(): array
    {
        return [
            'gradeSegments' => [],
            'topFailureHotspots' => [],
            'markCompletion' => [],
        ];
    }

    private function hostelSummary(array $hostelDashboard): ?array
    {
        $summary = $hostelDashboard['summary'];

        if (($summary['totalCapacity'] ?? 0) === 0) {
            return null;
        }

        return [
            'occupancyRate' => (int) $summary['occupancyRate'],
            'availableBeds' => (int) $summary['availableBeds'],
        ];
    }

    /**
     * @return Collection<int, object{department_id: int, department_name: string, student_count: int}>
     */
    private function confirmedStudentsByDepartment(int $intakePeriodId): Collection
    {
        $query = DB::table('departments')
            ->select(
                'departments.id as department_id',
                'departments.name as department_name',
                DB::raw('COUNT(DISTINCT student_applications.id) as student_count'),
            )
            ->join('institution_departments', 'institution_departments.department_id', '=', 'departments.id')
            ->join('student_applications', 'student_applications.institution_department_id', '=', 'institution_departments.id')
            ->where('departments.is_academic', true)
            ->where('student_applications.intake_period_id', $intakePeriodId)
            ->whereNull('student_applications.deleted_at')
            ->whereExists(function (QueryBuilder $exists): void {
                $exists->select(DB::raw(1))
                    ->from('class_lists')
                    ->whereColumn('class_lists.student_application_id', 'student_applications.id')
                    ->whereNull('class_lists.deleted_at')
                    ->where('class_lists.attributes->identity_confirmed', true)
                    ->where('class_lists.attributes->disability_confirmed', true)
                    ->where('class_lists.attributes->names_confirmed', true);
            });

        if ($this->isDepartmentUser) {
            $query->whereIn('institution_departments.id', $this->userDepartments);
        }

        return $query
            ->groupBy('departments.id', 'departments.name')
            ->get();
    }
}
