<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\Shared\WorkflowStepEnum;
use App\Helpers\StudentHelper;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Users\User;
use App\Services\Finance\StudentLedgerService;
use Illuminate\Support\Str;

class StudentPortalDashboardService
{
    private const int MAX_MODULES = 6;

    private const int MAX_ACTIVITIES = 5;

    public function __construct(
        protected StudentProgrammeDataService $programmeDataService,
        protected StudentLedgerService $ledgerService,
        protected StudentPortalTermDetailsService $termDetailsService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        $student = $user->studentProfile;

        abort_if($student === null, 403);

        $programmes = $this->programmeDataService->buildProgrammesForStudent($student);
        $activeSemester = $this->resolveActiveSemester($programmes);
        $modules = $activeSemester['module'] ?? [];

        $scoredModules = collect($modules)->filter(fn (array $module): bool => $module['score'] !== null);
        $averageCourseWorkScore = $scoredModules->isNotEmpty()
            ? round($scoredModules->avg('score'), 1)
            : null;

        $totalModuleHours = (int) collect($modules)->sum(fn (array $module): int => (int) ($module['durationInHours'] ?? 0));

        $dashboardModules = collect($modules)
            ->take(self::MAX_MODULES)
            ->map(fn (array $module): array => [
                'id' => $module['id'],
                'code' => $module['code'],
                'name' => $module['name'],
                'score' => $module['score'],
                'gradeDisplay' => $this->moduleGradeDisplay($module),
                'progressPercent' => $module['score'] !== null ? (int) round($module['score']) : 0,
            ])
            ->values()
            ->all();

        $applicationStats = $this->applicationStats($student);
        $oLevelSubjectCount = StudentHelper::getStudentOLevelResultsJoinedToSubjects($student)->count();
        $termDetails = $this->termDetailsService->build($student, $activeSemester);

        $payload = [
            'calendarType' => $termDetails['calendarType'],
            'activeModuleCount' => count($modules),
            'totalModuleHours' => $totalModuleHours,
            'averageCourseWorkScore' => $averageCourseWorkScore,
            'oLevelSubjectCount' => $oLevelSubjectCount,
            'applicationCount' => $applicationStats['total'],
            'pendingApplicationCount' => $applicationStats['pending'],
            'modules' => $dashboardModules,
            'activities' => $this->buildActivities($modules, $applicationStats['pendingPrograms']),
            'notices' => [],
            'currentTerm' => $termDetails['currentTerm'],
            'nextTerm' => $termDetails['nextTerm'],
        ];

        if ($user->can('manageOwnStudentFinancialDetails:students')) {
            $ledger = $this->ledgerService->build($student);
            $summary = $ledger['summary'];

            $payload['financial'] = [
                'paidPercent' => $summary['paidPercent'],
                'outstandingBalance' => $summary['outstandingBalance'],
                'totalInvoiced' => $summary['totalInvoiced'],
                'totalPayments' => $summary['totalPayments'],
            ];

            if ((float) $summary['outstandingBalance'] > 0 && count($payload['activities']) < self::MAX_ACTIVITIES) {
                array_unshift($payload['activities'], [
                    'type' => 'financial',
                    'message' => __('students.dashboard_activity_outstanding_balance', [
                        'amount' => $summary['outstandingBalance'],
                    ]),
                    'severity' => 'warning',
                ]);
                $payload['activities'] = array_slice($payload['activities'], 0, self::MAX_ACTIVITIES);
            }
        }

        return $payload;
    }

    /**
     * @param  list<array<string, mixed>>  $programmes
     * @return array<string, mixed>
     */
    private function resolveActiveSemester(array $programmes): array
    {
        $activeProgramme = collect($programmes)->firstWhere('isActive', true)
            ?? ($programmes[0] ?? null);

        if ($activeProgramme === null) {
            return ['module' => []];
        }

        $semesters = $activeProgramme['semesters'] ?? [];
        $activeSemester = collect($semesters)->first(
            fn (array $semester): bool => $this->isActiveEnrolmentStatus($semester['status'] ?? null)
        );

        if ($activeSemester !== null) {
            return $activeSemester;
        }

        return collect($semesters)->last() ?? ['module' => []];
    }

    /**
     * @return array{total: int, pending: int, pendingPrograms: list<StudentApplication>}
     */
    private function applicationStats(Student $student): array
    {
        $programs = StudentApplication::query()
            ->where('student_id', $student->id)
            ->with([
                'departmentWorkflowStep.workflowStep',
                'departmentCourse.course',
            ])
            ->get();

        $terminalSlugs = [
            WorkflowStepEnum::ENROLLED->slug(),
            WorkflowStepEnum::ACCEPTED->slug(),
            WorkflowStepEnum::REJECTED->slug(),
        ];

        $pendingPrograms = $programs->filter(function (StudentApplication $program) use ($terminalSlugs): bool {
            $slug = Str::slug((string) ($program->departmentWorkflowStep?->workflowStep?->name ?? ''));

            return ! in_array($slug, $terminalSlugs, true);
        });

        return [
            'total' => $programs->count(),
            'pending' => $pendingPrograms->count(),
            'pendingPrograms' => $pendingPrograms->values()->all(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $modules
     * @param  list<StudentApplication>  $pendingPrograms
     * @return list<array{type: string, message: string, severity: string}>
     */
    private function buildActivities(array $modules, array $pendingPrograms): array
    {
        $activities = [];

        foreach ($pendingPrograms as $program) {
            if (count($activities) >= self::MAX_ACTIVITIES) {
                break;
            }

            $stepName = $program->departmentWorkflowStep?->workflowStep?->name ?? __('students.application_in_progress');
            $courseName = $program->departmentCourse?->course?->name ?? __('students.application');

            $activities[] = [
                'type' => 'application',
                'message' => __('students.dashboard_activity_application', [
                    'course' => $courseName,
                    'step' => $stepName,
                ]),
                'severity' => 'warning',
            ];
        }

        foreach ($modules as $module) {
            if (count($activities) >= self::MAX_ACTIVITIES) {
                break;
            }

            $courseWork = $module['courseWork'] ?? null;

            if ($courseWork === null) {
                continue;
            }

            $hasPartialMarks = collect($courseWork['assessments'] ?? [])
                ->contains(fn (array $assessment): bool => $assessment['mark'] !== null);
            $hasTotal = ($courseWork['aggregation']['courseWorkTotal60'] ?? null) !== null;

            if ($hasPartialMarks && ! $hasTotal) {
                $activities[] = [
                    'type' => 'course_work',
                    'message' => __('students.dashboard_activity_course_work', [
                        'module' => $module['code'] ?? $module['name'],
                    ]),
                    'severity' => 'info',
                ];
            }
        }

        return $activities;
    }

    /**
     * @param  array<string, mixed>  $module
     */
    private function moduleGradeDisplay(array $module): string
    {
        $courseWork = $module['courseWork'] ?? null;
        $total = $courseWork['aggregation']['courseWorkTotal60'] ?? null;

        if ($total !== null) {
            return (string) (int) round($total);
        }

        $hasPartialMarks = collect($courseWork['assessments'] ?? [])
            ->contains(fn (array $assessment): bool => $assessment['mark'] !== null);

        if ($hasPartialMarks) {
            return __('students.course_work_in_progress');
        }

        if (! empty($module['grade'])) {
            return (string) $module['grade'];
        }

        return __('students.not_available');
    }

    private function isActiveEnrolmentStatus(?string $status): bool
    {
        return Str::lower(trim((string) $status)) === 'active';
    }
}
