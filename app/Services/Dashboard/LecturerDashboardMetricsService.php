<?php

namespace App\Services\Dashboard;

use App\Enums\Institution\ModeOfStudyEnum;
use App\Helpers\Helper;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Institution\AssessmentType;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Students\StudentEnrolment;
use App\Models\Users\User;
use App\Services\AcademicCalendars\CourseWorkAggregationService;
use App\Services\Lecturer\LecturerAssignmentResolver;
use App\Support\AcademicCalendars\CourseWorkGradeBand;
use App\Support\Institution\CourseSyllabusModulePeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LecturerDashboardMetricsService
{
    /** @var array<int, list<array{id: int, name: string, weightPercent: int|null}>> */
    private array $assessmentTypesByMode = [];

    /** @var array<int, list<CourseSyllabusModule>> */
    private array $modulesByClassConfigId = [];

    public function __construct(
        private readonly CourseWorkAggregationService $aggregationService,
        private readonly LecturerAssignmentResolver $assignmentResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        $resolved = $this->assignmentResolver->resolveForUser($user);
        $moduleResults = $this->moduleResults($resolved);
        $gradedResults = $this->gradedResults($moduleResults);

        return [
            'summary' => $this->summary($gradedResults, $moduleResults, $resolved),
            'attendance' => null,
            'topPerformingStudents' => $this->rankedStudents($gradedResults, 'desc', 5),
            'lowPerformingStudents' => $this->rankedStudents($gradedResults, 'asc', 5),
            'riskyStudents' => $this->riskyStudents($gradedResults),
            'missingCourseWork' => $this->missingCourseWork($moduleResults),
            'priorityAlerts' => $this->priorityAlerts($moduleResults, $gradedResults),
            'modules' => $this->moduleSummaries($moduleResults, $resolved),
            'quickActions' => $this->quickActions($user),
        ];
    }

    /**
     * @param  array{staff: mixed, classIds: list<int>, moduleIds: list<int>, assignmentKeys: list<string>, tutorClassIds: list<int>}  $resolved
     * @return list<array<string, mixed>>
     */
    private function moduleResults(array $resolved): array
    {
        if ($resolved['assignmentKeys'] === []) {
            return [];
        }

        $assignmentKeySet = array_fill_keys($resolved['assignmentKeys'], true);
        $academicCalendar = Helper::resolveAcademicCalendar();

        $enrolments = StudentEnrolment::query()
            ->where('academic_calendar_id', $academicCalendar->id)
            ->whereHas(
                'modeOfStudy',
                fn (Builder $query): Builder => $query->where('name', '!=', ModeOfStudyEnum::OJET->value),
            )
            ->whereHas(
                'academicCalendarStudentEnrolment',
                fn (Builder $query): Builder => $query->whereIn('academic_calendar_class_id', $resolved['classIds']),
            )
            ->with([
                'student.user',
                'institutionDepartment.department',
                'departmentLevel.level',
                'departmentCourse.course',
                'academicCalendarStudentEnrolment.academicCalendarClass.classConfig',
            ])
            ->get();

        if ($enrolments->isEmpty()) {
            return [];
        }

        $enrolmentIds = $enrolments->pluck('id')->all();
        $marks = CourseWorkMark::query()
            ->whereIn('student_enrolment_id', $enrolmentIds)
            ->get()
            ->groupBy(fn (CourseWorkMark $mark): string => $mark->student_enrolment_id.'-'.$mark->course_syllabus_module_id);

        $results = [];

        foreach ($enrolments as $enrolment) {
            $classAssignment = $enrolment->academicCalendarStudentEnrolment;
            $class = $classAssignment?->academicCalendarClass;

            if ($class === null) {
                continue;
            }

            $classConfig = $class->classConfig ?? ClassConfig::query()->find($class->class_config_id);

            if ($classConfig === null) {
                continue;
            }

            $modules = $this->modulesForClassConfig($classConfig);
            $assessmentTypes = $this->assessmentTypesForMode((int) $enrolment->mode_of_study_id);
            $studentName = $enrolment->student?->user?->full_name
                ?? __('dashboard.lecturer_unknown_student');

            foreach ($modules as $module) {
                $classId = (int) $class->id;
                $moduleId = (int) $module->id;
                $key = $this->assignmentResolver->assignmentKey($classId, $moduleId);

                if (! isset($assignmentKeySet[$key])) {
                    continue;
                }

                $markKey = $enrolment->id.'-'.$module->id;
                $markGroup = $marks->get($markKey, collect());

                if ($module->capture_mark_only) {
                    $saved = $markGroup->firstWhere(fn (CourseWorkMark $mark): bool => $mark->assessment_type_id === null);
                    $hasNoMarks = $saved === null || $saved->mark === null;
                    $isComplete = $saved !== null && $saved->mark !== null;
                    $total = $isComplete ? (int) $saved->mark : null;

                    $results[] = [
                        'studentEnrolmentId' => (int) $enrolment->id,
                        'studentId' => (int) $enrolment->student_id,
                        'studentName' => $studentName,
                        'moduleId' => $moduleId,
                        'moduleName' => (string) $module->title,
                        'moduleCode' => (string) ($module->code ?? ''),
                        'academicCalendarClassId' => $classId,
                        'className' => (string) $class->name,
                        'isComplete' => $isComplete,
                        'hasNoMarks' => $hasNoMarks,
                        'band' => null,
                        'courseWorkTotal60' => $total,
                    ];

                    continue;
                }

                if ($assessmentTypes === []) {
                    continue;
                }

                $hasNoMarks = $markGroup->isEmpty()
                    || $markGroup->every(fn (CourseWorkMark $mark): bool => $mark->mark === null);

                $assessments = collect($assessmentTypes)->map(function (array $type) use ($markGroup): array {
                    $saved = $markGroup->firstWhere('assessment_type_id', $type['id']);

                    return [
                        'assessmentTypeId' => $type['id'],
                        'assessmentTypeName' => $type['name'],
                        'mark' => $saved?->mark,
                        'remark' => $saved?->remark,
                    ];
                })->values()->all();

                $aggregation = $this->aggregationService->aggregateStudentModule($assessmentTypes, $assessments);
                $band = CourseWorkGradeBand::classify($aggregation['courseWorkTotal60']);

                $results[] = [
                    'studentEnrolmentId' => (int) $enrolment->id,
                    'studentId' => (int) $enrolment->student_id,
                    'studentName' => $studentName,
                    'moduleId' => $moduleId,
                    'moduleName' => (string) $module->title,
                    'moduleCode' => (string) ($module->code ?? ''),
                    'academicCalendarClassId' => $classId,
                    'className' => (string) $class->name,
                    'isComplete' => $aggregation['isComplete'],
                    'hasNoMarks' => $hasNoMarks,
                    'band' => $band,
                    'courseWorkTotal60' => $aggregation['courseWorkTotal60'],
                ];
            }
        }

        return $results;
    }

    /**
     * @param  list<array<string, mixed>>  $moduleResults
     * @return list<array<string, mixed>>
     */
    private function gradedResults(array $moduleResults): array
    {
        $graded = [];

        foreach ($moduleResults as $row) {
            if (! $row['isComplete'] || $row['courseWorkTotal60'] === null) {
                continue;
            }

            $graded[] = $row;
        }

        return $graded;
    }

    /**
     * @param  list<array<string, mixed>>  $gradedResults
     * @param  list<array<string, mixed>>  $moduleResults
     * @param  array{classIds: list<int>, moduleIds: list<int>, assignmentKeys: list<string>}  $resolved
     * @return array<string, float|int|null>
     */
    private function summary(array $gradedResults, array $moduleResults, array $resolved): array
    {
        $total = count($gradedResults);
        $expected = count($moduleResults);
        $completeCount = count(array_filter($moduleResults, fn (array $row): bool => $row['isComplete']));
        $markCompletionRate = $expected > 0 ? round(($completeCount / $expected) * 100, 1) : null;

        $passRate = null;
        $averageMark = null;
        $atRiskStudentCount = null;

        if ($total > 0) {
            $passCount = count(array_filter(
                $gradedResults,
                fn (array $row): bool => $row['band'] !== null && CourseWorkGradeBand::isPassing((string) $row['band']),
            ));
            $passRate = round(($passCount / $total) * 100, 1);

            $sum = array_sum(array_map(
                fn (array $row): int => (int) $row['courseWorkTotal60'],
                $gradedResults,
            ));
            $averageMark = round($sum / $total, 1);
        }

        $risky = $this->riskyStudents($gradedResults);
        if ($risky !== []) {
            $atRiskStudentCount = count($risky);
        } elseif ($gradedResults !== []) {
            $atRiskStudentCount = 0;
        }

        return [
            'passRate' => $passRate,
            'averageMark' => $averageMark,
            'modulesCount' => count($resolved['moduleIds']),
            'classesCount' => count($resolved['classIds']),
            'markCompletionRate' => $markCompletionRate,
            'atRiskStudentCount' => $atRiskStudentCount,
            'missingCourseWorkCount' => count(array_filter(
                $moduleResults,
                fn (array $row): bool => ! $row['isComplete'],
            )),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $gradedResults
     * @return list<array<string, mixed>>
     */
    private function rankedStudents(array $gradedResults, string $direction, int $limit): array
    {
        if ($gradedResults === []) {
            return [];
        }

        $byStudent = collect($gradedResults)
            ->groupBy('studentEnrolmentId')
            ->map(function (Collection $rows): array {
                $totals = $rows->pluck('courseWorkTotal60')->filter()->map(fn ($v): int => (int) $v);
                $average = $totals->avg();

                return [
                    'studentEnrolmentId' => (int) $rows->first()['studentEnrolmentId'],
                    'studentId' => (int) $rows->first()['studentId'],
                    'studentName' => (string) $rows->first()['studentName'],
                    'averageMark' => $average !== null ? round((float) $average, 1) : null,
                    'modulesCount' => $rows->count(),
                ];
            })
            ->filter(fn (array $row): bool => $row['averageMark'] !== null)
            ->values();

        $sorted = $direction === 'asc'
            ? $byStudent->sortBy('averageMark')
            : $byStudent->sortByDesc('averageMark');

        return $sorted->take($limit)->values()->all();
    }

    /**
     * @param  list<array<string, mixed>>  $gradedResults
     * @return list<array<string, mixed>>
     */
    private function riskyStudents(array $gradedResults): array
    {
        $failuresByEnrolment = [];

        foreach ($gradedResults as $result) {
            if ($result['band'] !== CourseWorkGradeBand::FAIL) {
                continue;
            }

            $enrolmentId = (int) $result['studentEnrolmentId'];
            $failuresByEnrolment[$enrolmentId] ??= [
                'studentEnrolmentId' => $enrolmentId,
                'studentId' => (int) $result['studentId'],
                'studentName' => (string) $result['studentName'],
                'failCount' => 0,
            ];
            $failuresByEnrolment[$enrolmentId]['failCount']++;
        }

        return array_values(array_filter(
            $failuresByEnrolment,
            fn (array $row): bool => $row['failCount'] >= 2,
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $moduleResults
     * @return list<array<string, mixed>>
     */
    private function missingCourseWork(array $moduleResults): array
    {
        return collect($moduleResults)
            ->filter(fn (array $row): bool => ! $row['isComplete'])
            ->groupBy(fn (array $row): string => $row['academicCalendarClassId'].'-'.$row['moduleId'])
            ->map(function (Collection $rows): array {
                $first = $rows->first();

                return [
                    'academicCalendarClassId' => (int) $first['academicCalendarClassId'],
                    'className' => (string) $first['className'],
                    'moduleId' => (int) $first['moduleId'],
                    'moduleName' => (string) $first['moduleName'],
                    'moduleCode' => (string) $first['moduleCode'],
                    'incompleteCount' => $rows->count(),
                    'outstandingCount' => $rows->filter(fn (array $row): bool => $row['hasNoMarks'])->count(),
                ];
            })
            ->sortByDesc('incompleteCount')
            ->take(10)
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $moduleResults
     * @param  list<array<string, mixed>>  $gradedResults
     * @return list<array{severity: string, message: string, updatedAt: string|null}>
     */
    private function priorityAlerts(array $moduleResults, array $gradedResults): array
    {
        $alerts = [];
        $missing = $this->missingCourseWork($moduleResults);

        if ($missing !== []) {
            $top = $missing[0];
            $alerts[] = [
                'severity' => 'warning',
                'message' => __('dashboard.lecturer_alert_missing_marks', [
                    'count' => $top['incompleteCount'],
                    'module' => $top['moduleName'],
                    'class' => $top['className'],
                ]),
                'updatedAt' => null,
            ];
        }

        $risky = $this->riskyStudents($gradedResults);

        if ($risky !== []) {
            $alerts[] = [
                'severity' => 'critical',
                'message' => __('dashboard.lecturer_alert_risky_students', [
                    'count' => count($risky),
                ]),
                'updatedAt' => null,
            ];
        }

        $failCount = count(array_filter(
            $gradedResults,
            fn (array $row): bool => $row['band'] === CourseWorkGradeBand::FAIL,
        ));

        if ($failCount > 0 && count($gradedResults) > 0) {
            $failRate = (int) round(($failCount / count($gradedResults)) * 100);

            if ($failRate >= 30) {
                $alerts[] = [
                    'severity' => 'warning',
                    'message' => __('dashboard.lecturer_alert_high_fail_rate', [
                        'rate' => $failRate,
                    ]),
                    'updatedAt' => null,
                ];
            }
        }

        return $alerts;
    }

    /**
     * @param  list<array<string, mixed>>  $moduleResults
     * @param  array{moduleIds: list<int>}  $resolved
     * @return list<array<string, mixed>>
     */
    private function moduleSummaries(array $moduleResults, array $resolved): array
    {
        if ($resolved['moduleIds'] === []) {
            return [];
        }

        $byModule = collect($moduleResults)
            ->groupBy('moduleId')
            ->map(function (Collection $rows): array {
                $graded = $rows->filter(
                    fn (array $row): bool => $row['isComplete'] && $row['courseWorkTotal60'] !== null,
                );
                $passCount = $graded->filter(
                    fn (array $row): bool => $row['band'] !== null && CourseWorkGradeBand::isPassing((string) $row['band']),
                )->count();
                $gradedCount = $graded->count();

                return [
                    'moduleId' => (int) $rows->first()['moduleId'],
                    'moduleName' => (string) $rows->first()['moduleName'],
                    'moduleCode' => (string) $rows->first()['moduleCode'],
                    'classesCount' => $rows->pluck('academicCalendarClassId')->unique()->count(),
                    'studentsCount' => $rows->pluck('studentEnrolmentId')->unique()->count(),
                    'passRate' => $gradedCount > 0 ? round(($passCount / $gradedCount) * 100, 1) : null,
                    'averageMark' => $gradedCount > 0
                        ? round((float) $graded->avg('courseWorkTotal60'), 1)
                        : null,
                    'incompleteCount' => $rows->filter(fn (array $row): bool => ! $row['isComplete'])->count(),
                ];
            })
            ->values()
            ->all();

        return $byModule;
    }

    /**
     * @return list<array{key: string, label: string, url: string|null, enabled: bool}>
     */
    private function quickActions(User $user): array
    {
        return [
            [
                'key' => 'classes',
                'label' => __('dashboard.lecturer_action_classes'),
                'url' => route('teaching.classes.index'),
                'enabled' => $user->can('view:lecturer-classes'),
            ],
            [
                'key' => 'modules',
                'label' => __('dashboard.lecturer_action_modules'),
                'url' => route('teaching.modules.index'),
                'enabled' => $user->can('view:lecturer-modules'),
            ],
            [
                'key' => 'enter_marks',
                'label' => __('dashboard.lecturer_action_enter_marks'),
                'url' => $user->can('view:course-work') || $user->can('update:course-work') || $user->can('viewAny:course-work')
                    ? route('teaching.classes.index')
                    : null,
                'enabled' => $user->can('view:course-work') || $user->can('update:course-work') || $user->can('viewAny:course-work'),
            ],
        ];
    }

    /**
     * @return list<CourseSyllabusModule>
     */
    private function modulesForClassConfig(ClassConfig $classConfig): array
    {
        $classConfigId = (int) $classConfig->id;

        if (array_key_exists($classConfigId, $this->modulesByClassConfigId)) {
            return $this->modulesByClassConfigId[$classConfigId];
        }

        $syllabusIds = array_values(array_map(
            'intval',
            array_filter($classConfig->course_syllabus_ids ?? []),
        ));

        if ($syllabusIds === []) {
            return $this->modulesByClassConfigId[$classConfigId] = [];
        }

        $slugPrefix = CourseSyllabusModulePeriod::slugPrefixForSyllabus($syllabusIds[0]);

        return $this->modulesByClassConfigId[$classConfigId] = CourseSyllabusModule::query()
            ->whereIn('course_syllabus_id', $syllabusIds)
            ->where(function ($query) use ($classConfig, $slugPrefix): void {
                CourseSyllabusModulePeriod::scopeForPeriod(
                    $query,
                    (int) $classConfig->academic_year_option_id,
                    $slugPrefix,
                );
            })
            ->orderBy('code')
            ->get()
            ->all();
    }

    /**
     * @return list<array{id: int, name: string, weightPercent: int|null}>
     */
    private function assessmentTypesForMode(int $modeOfStudyId): array
    {
        if (array_key_exists($modeOfStudyId, $this->assessmentTypesByMode)) {
            return $this->assessmentTypesByMode[$modeOfStudyId];
        }

        $types = AssessmentType::query()
            ->whereJsonContains('modes_of_study', $modeOfStudyId)
            ->orderBy('name')
            ->get()
            ->map(fn (AssessmentType $type): array => [
                'id' => (int) $type->id,
                'name' => $type->name,
                'weightPercent' => $type->weight_percent,
            ])
            ->values()
            ->all();

        return $this->assessmentTypesByMode[$modeOfStudyId] = $types;
    }
}
