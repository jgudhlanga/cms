<?php

namespace App\Services\Dashboard;

use App\Enums\HMS\HostelQueryPriorityEnum;
use App\Enums\HMS\HostelQueryStatusEnum;
use App\Helpers\Helper;
use App\Models\HMS\HostelQuery;
use App\Models\Institution\DepartmentCourse;
use App\Services\ApplicationMetricsService;
use Illuminate\Database\Query\Builder as QueryBuilder;
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
    ) {
        $this->isDepartmentUser = Helper::isDepartmentUser();
        $this->userDepartments = Helper::resolveUserDepartments() ?? [];
    }

    /**
     * @return array{
     *     summary: array<string, float|int|string|null>,
     *     enrolmentByDepartment: list<array<string, mixed>>,
     *     priorityAlerts: list<array<string, mixed>>
     * }
     */
    public function build(): array
    {
        $intakePeriod = Helper::resolveIntakePeriod();
        $enrolmentSummary = $this->applicationMetricsService->enrolmentSummaryMetrics();
        $academicDashboard = $this->academicDashboardMetricsService->build();
        $hostelSummary = $this->hostelSummary();

        return [
            'summary' => $this->summary(
                $intakePeriod !== null,
                $enrolmentSummary['confirmed'],
                $academicDashboard,
                $hostelSummary,
            ),
            'enrolmentByDepartment' => $this->enrolmentByDepartment($intakePeriod?->id),
            'priorityAlerts' => $this->priorityAlerts($enrolmentSummary, $academicDashboard),
        ];
    }

    /**
     * @param  array{applications: int, offersMade: int, confirmed: int, waitlisted: int}  $enrolmentSummary
     * @param  array<string, mixed>  $academicDashboard
     * @return array<string, float|int|string|null>
     */
    private function summary(
        bool $hasIntakePeriod,
        int $confirmedStudents,
        array $academicDashboard,
        ?array $hostelSummary,
    ): array {
        return [
            'totalStudents' => $hasIntakePeriod && ! ($this->isDepartmentUser && $this->userDepartments === [])
                ? $confirmedStudents
                : null,
            'totalStudentsSubtext' => null,
            'totalStudentsTrend' => null,
            'attendanceRate' => null,
            'attendanceSubtext' => null,
            'attendanceTrend' => null,
            'passRate' => $academicDashboard['summary']['passRate'],
            'passRateSubtext' => null,
            'passRateTrend' => null,
            'feeCollectionRate' => null,
            'feeCollectionSubtext' => null,
            'feeCollectionTrend' => null,
            'programmeCount' => $this->programmeCount(),
            'programmeSubtext' => null,
            'programmeTrend' => null,
            'departmentCount' => $this->departmentCount(),
            'departmentSubtext' => null,
            'departmentTrend' => null,
            'hostelOccupancyRate' => $hostelSummary['occupancyRate'] ?? null,
            'hostelAvailableBeds' => $hostelSummary['availableBeds'] ?? null,
            'hostelSubtext' => null,
            'hostelTrend' => null,
            'atRiskStudents' => $this->academicDashboardMetricsService->atRiskStudentCount(),
            'atRiskSubtext' => null,
            'atRiskTrend' => null,
        ];
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
     * @param  array{applications: int, offersMade: int, confirmed: int, waitlisted: int}  $enrolmentSummary
     * @param  array<string, mixed>  $academicDashboard
     * @return list<array{severity: string, message: string, updatedAt: string|null}>
     */
    private function priorityAlerts(array $enrolmentSummary, array $academicDashboard): array
    {
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
     * @return array{occupancyRate: int, availableBeds: int}|null
     */
    private function hostelSummary(): ?array
    {
        $hostelDashboard = $this->hostelDashboardMetricsService->build();
        $summary = $hostelDashboard['summary'];

        if (($summary['totalCapacity'] ?? 0) === 0) {
            return null;
        }

        return [
            'occupancyRate' => (int) $summary['occupancyRate'],
            'availableBeds' => (int) $summary['availableBeds'],
        ];
    }

    private function programmeCount(): int
    {
        $query = DepartmentCourse::query();

        if ($this->isDepartmentUser) {
            $query->whereIn('institution_department_id', $this->userDepartments);
        }

        return $query->count();
    }

    private function departmentCount(): int
    {
        $query = DB::table('departments')
            ->where('departments.is_academic', true)
            ->whereNull('departments.deleted_at');

        if ($this->isDepartmentUser) {
            $query->join('institution_departments', 'institution_departments.department_id', '=', 'departments.id')
                ->whereIn('institution_departments.id', $this->userDepartments);
        }

        return (int) $query->distinct('departments.id')->count('departments.id');
    }

    /**
     * @return \Illuminate\Support\Collection<int, object{department_id: int, department_name: string, student_count: int}>
     */
    private function confirmedStudentsByDepartment(int $intakePeriodId): \Illuminate\Support\Collection
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
