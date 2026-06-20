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
    public function build(): array
    {
        $intakePeriod = Helper::resolveIntakePeriod();
        $enrolmentSummary = $this->applicationMetricsService->enrolmentSummaryMetrics();
        $academicDashboard = $this->academicDashboardMetricsService->build();
        $hostelDashboard = $this->hostelDashboardMetricsService->build();
        $staffDashboard = $this->staffDashboardMetricsService->build();
        $hostelSummary = $this->hostelSummary($hostelDashboard);

        return [
            'summary' => $this->summary(
                $academicDashboard,
                $hostelSummary,
                $staffDashboard,
            ),
            'enrolmentFunnel' => $this->enrolmentFunnel($enrolmentSummary),
            'academicSnapshot' => $this->academicSnapshot($academicDashboard),
            'quickInsights' => $this->quickInsights($academicDashboard, $staffDashboard),
            'enrolmentByDepartment' => $this->enrolmentByDepartment($intakePeriod?->id),
            'priorityAlerts' => $this->priorityAlerts(
                $enrolmentSummary,
                $academicDashboard,
                $hostelDashboard,
            ),
        ];
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
    ): array {
        $courseWorkStatus = $academicDashboard['courseWorkStatus'];
        $academicSummary = $academicDashboard['summary'];
        $staffSummary = $staffDashboard['summary'];

        return [
            'passRate' => $academicSummary['passRate'],
            'passRateSubtext' => $this->passRateSubtext($academicSummary),
            'markCompletionRate' => $courseWorkStatus['completeRate'],
            'markCompletionSubtext' => $this->markCompletionSubtext($courseWorkStatus),
            'atRiskStudents' => $this->academicDashboardMetricsService->atRiskStudentCount(),
            'atRiskSubtext' => __('dashboard.overview_at_risk_subtext'),
            'hostelOccupancyRate' => $hostelSummary['occupancyRate'] ?? null,
            'hostelAvailableBeds' => $hostelSummary['availableBeds'] ?? null,
            'hostelSubtext' => $hostelSummary !== null
                ? __('dashboard.overview_hostel_beds_available', ['count' => $hostelSummary['availableBeds']])
                : null,
            'totalStaff' => $staffSummary['totalStaff'],
            'totalStaffSubtext' => __('dashboard.staff_academic_admin_subtext', [
                'academic' => $staffSummary['academicCount'],
                'admin' => $staffSummary['adminCount'],
            ]),
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
    private function quickInsights(array $academicDashboard, array $staffDashboard): array
    {
        $insights = [];

        $attachmentStatus = $academicDashboard['attachmentStatus'] ?? null;

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

        $passRates = $academicDashboard['passRateByDepartment'] ?? [];

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

        $ratios = $staffDashboard['lecturerRatios'] ?? [];

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
    ): array {
        $alerts = [];

        $topHotspot = $academicDashboard['moduleFailureHotspots'][0] ?? null;

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

        $courseWorkStatus = $academicDashboard['courseWorkStatus'];
        $topMissingDept = $academicDashboard['missingMarksByDepartment'][0] ?? null;

        if (($courseWorkStatus['incompleteCount'] ?? 0) > 0) {
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

        if (($courseWorkStatus['outstandingCount'] ?? 0) > 0) {
            $alerts[] = [
                'severity' => 'warning',
                'message' => __('dashboard.overview_alert_outstanding_marks', [
                    'count' => $courseWorkStatus['outstandingCount'],
                ]),
                'updatedAt' => null,
            ];
        }

        $topLecturer = $academicDashboard['lecturerMarkingStats'][0] ?? null;

        if (is_array($topLecturer) && ($topLecturer['incompleteRate'] ?? 0) >= 25) {
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

        $highPriorityQueries = (int) ($hostelDashboard['queryStats']['highPriority'] ?? 0);

        if ($highPriorityQueries > 0) {
            $alerts[] = [
                'severity' => 'critical',
                'message' => __('dashboard.overview_alert_hostel_high_priority', [
                    'count' => $highPriorityQueries,
                ]),
                'updatedAt' => null,
            ];
        }

        if ($enrolmentSummary['provisional'] > 0) {
            $alerts[] = [
                'severity' => 'warning',
                'message' => __('dashboard.overview_alert_provisional', [
                    'count' => $enrolmentSummary['provisional'],
                ]),
                'updatedAt' => null,
            ];
        }

        if ($enrolmentSummary['waitlisted'] > 0) {
            $alerts[] = [
                'severity' => 'warning',
                'message' => __('dashboard.overview_alert_waitlisted', [
                    'count' => $enrolmentSummary['waitlisted'],
                ]),
                'updatedAt' => null,
            ];
        }

        if ($enrolmentSummary['applications'] > 0) {
            $alerts[] = [
                'severity' => 'info',
                'message' => __('dashboard.overview_alert_applications', [
                    'count' => $enrolmentSummary['applications'],
                ]),
                'updatedAt' => null,
            ];
        }

        $attachmentStatus = $academicDashboard['attachmentStatus'] ?? null;

        if (is_array($attachmentStatus) && ($attachmentStatus['placed'] ?? 0) > 0) {
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
                DB::raw('COUNT(DISTINCT student_programs.id) as student_count'),
            )
            ->join('institution_departments', 'institution_departments.department_id', '=', 'departments.id')
            ->join('student_programs', 'student_programs.institution_department_id', '=', 'institution_departments.id')
            ->where('departments.is_academic', true)
            ->where('student_programs.intake_period_id', $intakePeriodId)
            ->whereNull('student_programs.deleted_at')
            ->whereExists(function (QueryBuilder $exists): void {
                $exists->select(DB::raw(1))
                    ->from('class_lists')
                    ->whereColumn('class_lists.student_program_id', 'student_programs.id')
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
