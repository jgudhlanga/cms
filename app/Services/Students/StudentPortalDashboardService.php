<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\Shared\WorkflowStepEnum;
use App\Helpers\StudentHelper;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Users\User;
use App\Services\Assessments\MissingMarksQueryService;
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
        protected MissingMarksQueryService $missingMarksQuery,
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
                'statusKey' => $this->moduleStatusKey($module),
                'progressPercent' => $module['score'] !== null ? (int) round($module['score']) : 0,
                'examGrade' => $module['grade'] ?? null,
                'examSession' => $module['examSession'] ?? null,
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
            'notices' => $this->missingMarkNotices($student, $activeSemester),
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
            fn (array $semester): bool => ($semester['isCurrent'] ?? false) === true
        );

        if ($activeSemester !== null) {
            return $activeSemester;
        }

        return collect($semesters)->first() ?? ['module' => []];
    }

    /**
     * @return array{total: int, pending: int, pendingPrograms: list<StudentApplication>}
     */
    private function applicationStats(Student $student): array
    {
        $programs = StudentApplication::query()
            ->where('student_id', $student->id)
            ->with([
                'workflowStep',
                'departmentCourse.course',
            ])
            ->get();

        $terminalSlugs = [
            WorkflowStepEnum::ENROLLED->slug(),
            WorkflowStepEnum::ACCEPTED->slug(),
            WorkflowStepEnum::REJECTED->slug(),
        ];

        $pendingPrograms = $programs->filter(function (StudentApplication $program) use ($terminalSlugs): bool {
            $slug = Str::slug((string) ($program->workflowStep?->name ?? ''));

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

            $stepName = $program->workflowStep?->name ?? __('students.application_in_progress');
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

    /**
     * @param  array<string, mixed>  $module
     * @return 'graded'|'in_progress'|'not_graded'
     */
    private function moduleStatusKey(array $module): string
    {
        if (! empty($module['grade'])) {
            return 'graded';
        }

        $courseWork = $module['courseWork'] ?? null;
        $total = $courseWork['aggregation']['courseWorkTotal60'] ?? null;

        if ($total !== null) {
            return 'graded';
        }

        $hasPartialMarks = collect($courseWork['assessments'] ?? [])
            ->contains(fn (array $assessment): bool => $assessment['mark'] !== null);

        return $hasPartialMarks ? 'in_progress' : 'not_graded';
    }

    private function isActiveEnrolmentStatus(?string $status): bool
    {
        return Str::lower(trim((string) $status)) === 'active';
    }

    /**
     * @param  array<string, mixed>  $activeSemester
     * @return list<array{id: string, title: string, message: string, publishedAt: string|null}>
     */
    private function missingMarkNotices(Student $student, array $activeSemester): array
    {
        $enrolment = $this->resolveEnrolment($student, $activeSemester);

        if (! $enrolment instanceof StudentEnrolment || $enrolment->academic_calendar_id === null) {
            return [];
        }

        $enrolment->loadMissing([
            'student.user',
            'institutionDepartment.department',
            'academicCalendarStudentEnrolment.academicCalendarClass.classConfig',
        ]);

        $calendars = AssessmentCalendar::query()
            ->where('academic_calendar_id', (int) $enrolment->academic_calendar_id)
            ->with('assessmentType')
            ->orderBy('end_date')
            ->get();

        $today = now()->startOfDay();
        $notices = [];

        foreach ($calendars as $calendar) {
            if (! $calendar->isInNotificationWindow($today)) {
                continue;
            }

            $rows = $this->missingMarksQuery->forStudentEnrolment($enrolment, $calendar);
            $assessmentName = (string) ($calendar->assessmentType?->name ?? __('trans.assessment_type'));

            foreach ($rows as $row) {
                $notices[] = [
                    'id' => 'missing-marks-'.$calendar->id.'-'.$row['moduleId'],
                    'title' => __('assessments.student_notice_missing_marks_title', [
                        'assessment' => $assessmentName,
                    ]),
                    'message' => __('assessments.student_notice_missing_marks', [
                        'assessment' => $assessmentName,
                        'module' => $row['moduleName'],
                    ]),
                    'publishedAt' => $calendar->first_notification_date?->toDateString(),
                ];
            }
        }

        return $notices;
    }

    /**
     * @param  array<string, mixed>  $activeSemester
     */
    private function resolveEnrolment(Student $student, array $activeSemester): ?StudentEnrolment
    {
        $enrolmentId = $activeSemester['studentEnrolmentId'] ?? null;

        if ($enrolmentId !== null) {
            $enrolment = StudentEnrolment::query()->find($enrolmentId);

            if ($enrolment instanceof StudentEnrolment) {
                return $enrolment;
            }
        }

        $student->loadMissing('latestEnrolment');

        return $student->latestEnrolment;
    }
}
