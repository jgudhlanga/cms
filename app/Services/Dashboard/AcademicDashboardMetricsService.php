<?php

namespace App\Services\Dashboard;

use App\Enums\AcademicCalendars\ClassMetaDataTypeEnum;
use App\Enums\Institution\ModeOfStudyEnum;
use App\Helpers\Helper;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarClassMetaData;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\ClassMetaDataType;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Institution\AssessmentType;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Students\StudentEnrolment;
use App\Services\AcademicCalendars\CourseWorkAggregationService;
use App\Support\AcademicCalendars\CourseWorkGradeBand;
use App\Support\Institution\CourseSyllabusModulePeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AcademicDashboardMetricsService
{
    protected bool $isDepartmentUser = false;

    /** @var list<int> */
    protected array $userDepartments = [];

    /** @var array<int, list<array{id: int, name: string, weightPercent: int|null}>> */
    private array $assessmentTypesByMode = [];

    /** @var array<int, list<CourseSyllabusModule>> */
    private array $modulesByClassConfigId = [];

    /** @var array<int, array{staffId: int, lecturerName: string}|null> */
    private array $lecturerByClassId = [];

    /** @var list<array<string, mixed>>|null */
    private ?array $cachedModuleResults = null;

    public function __construct(
        private readonly CourseWorkAggregationService $aggregationService,
    ) {
        $this->isDepartmentUser = Helper::isDepartmentUser();
        $this->userDepartments = Helper::resolveUserDepartments() ?? [];
    }

    public function atRiskStudentCount(): ?int
    {
        $failuresByEnrolment = [];

        foreach ($this->gradedResults() as $result) {
            if ($result['band'] !== CourseWorkGradeBand::FAIL) {
                continue;
            }

            $enrolmentId = $result['studentEnrolmentId'];
            $failuresByEnrolment[$enrolmentId] = ($failuresByEnrolment[$enrolmentId] ?? 0) + 1;
        }

        if ($failuresByEnrolment === []) {
            return null;
        }

        return count(array_filter($failuresByEnrolment, fn (int $count): bool => $count >= 2));
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $moduleResults = $this->moduleResults();
        $gradedResults = $this->gradedResultsFromModuleResults($moduleResults);
        $attachmentStatus = $this->attachmentStatus();

        return [
            'summary' => $this->summary($gradedResults, $moduleResults),
            'courseWorkStatus' => $this->courseWorkStatus($moduleResults),
            'gradeDistribution' => $this->gradeDistribution($gradedResults),
            'passRateByDepartment' => $this->passRateByDepartment($gradedResults),
            'passRateByLevel' => $this->passRateByLevel($gradedResults),
            'passRateByCourse' => $this->passRateByCourse($gradedResults),
            'moduleFailureHotspots' => $this->moduleFailureHotspots($gradedResults),
            'missingMarksByDepartment' => $this->missingMarksBreakdown($moduleResults, 'departmentId', 'departmentName'),
            'missingMarksByLevel' => $this->missingMarksBreakdown($moduleResults, 'levelId', 'levelName'),
            'missingMarksByCourse' => $this->missingMarksBreakdown($moduleResults, 'courseId', 'courseName'),
            'missingMarksByModule' => $this->missingMarksBreakdown($moduleResults, 'moduleId', 'moduleName'),
            'lecturerMarkingStats' => $this->lecturerMarkingStats($moduleResults),
            'attachmentStatus' => $attachmentStatus,
            'attachmentTotal' => $attachmentStatus['total'] ?? null,
            'attachmentCalendarYear' => $attachmentStatus['calendarYear'] ?? Helper::resolveAcademicCalendar()->calendar_year,
        ];
    }

    /**
     * @param  list<array{band: string, studentEnrolmentId: int, departmentId: int, departmentName: string, moduleId: int, moduleName: string}>  $gradedResults
     * @param  list<array<string, mixed>>  $moduleResults
     * @return array<string, float|int|null>
     */
    private function summary(array $gradedResults, array $moduleResults): array
    {
        $total = count($gradedResults);
        $expected = count($moduleResults);
        $completeCount = count(array_filter($moduleResults, fn (array $row): bool => $row['isComplete']));
        $markCompletionRate = $expected > 0 ? round(($completeCount / $expected) * 100, 1) : null;

        if ($total === 0) {
            return [
                'passRate' => null,
                'failRate' => null,
                'distinctionRate' => null,
                'probationCount' => null,
                'probationPercent' => null,
                'passRateTrend' => null,
                'failRateTrend' => null,
                'distinctionTrend' => null,
                'probationSubtext' => null,
                'markCompletionRate' => $markCompletionRate,
                'atRiskStudentCount' => $this->atRiskStudentCount(),
            ];
        }

        $passCount = 0;
        $failCount = 0;
        $distinctionCount = 0;

        foreach ($gradedResults as $result) {
            if (CourseWorkGradeBand::isPassing($result['band'])) {
                $passCount++;
            } else {
                $failCount++;
            }

            if ($result['band'] === CourseWorkGradeBand::DISTINCTION) {
                $distinctionCount++;
            }
        }

        return [
            'passRate' => round(($passCount / $total) * 100, 1),
            'failRate' => round(($failCount / $total) * 100, 1),
            'distinctionRate' => round(($distinctionCount / $total) * 100, 1),
            'probationCount' => null,
            'probationPercent' => null,
            'passRateTrend' => null,
            'failRateTrend' => null,
            'distinctionTrend' => null,
            'probationSubtext' => null,
            'markCompletionRate' => $markCompletionRate,
            'atRiskStudentCount' => $this->atRiskStudentCount(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $moduleResults
     * @return array<string, int|float|null>
     */
    private function courseWorkStatus(array $moduleResults): array
    {
        $expected = count($moduleResults);

        if ($expected === 0) {
            return [
                'expectedModuleResults' => 0,
                'completeCount' => 0,
                'completeRate' => null,
                'incompleteCount' => 0,
                'incompleteRate' => null,
                'outstandingCount' => 0,
            ];
        }

        $completeCount = 0;
        $incompleteCount = 0;
        $outstandingCount = 0;

        foreach ($moduleResults as $row) {
            if ($row['isComplete']) {
                $completeCount++;
            } else {
                $incompleteCount++;
            }

            if ($row['hasNoMarks']) {
                $outstandingCount++;
            }
        }

        return [
            'expectedModuleResults' => $expected,
            'completeCount' => $completeCount,
            'completeRate' => round(($completeCount / $expected) * 100, 1),
            'incompleteCount' => $incompleteCount,
            'incompleteRate' => round(($incompleteCount / $expected) * 100, 1),
            'outstandingCount' => $outstandingCount,
        ];
    }

    /**
     * @param  list<array{band: string, departmentId: int, departmentName: string, moduleId: int, moduleName: string}>  $gradedResults
     * @return array{segments: list<array<string, mixed>>}
     */
    private function gradeDistribution(array $gradedResults): array
    {
        $counts = [
            CourseWorkGradeBand::DISTINCTION => 0,
            CourseWorkGradeBand::MERIT => 0,
            CourseWorkGradeBand::PASS => 0,
            CourseWorkGradeBand::FAIL => 0,
        ];

        foreach ($gradedResults as $result) {
            $counts[$result['band']]++;
        }

        $total = array_sum($counts);

        if ($total === 0) {
            return ['segments' => []];
        }

        $definitions = [
            CourseWorkGradeBand::DISTINCTION => ['label' => __('dashboard.academic_grade_distinction'), 'color' => 'bg-blue-500'],
            CourseWorkGradeBand::MERIT => ['label' => __('dashboard.academic_grade_merit'), 'color' => 'bg-indigo-500'],
            CourseWorkGradeBand::PASS => ['label' => __('dashboard.academic_grade_pass'), 'color' => 'bg-emerald-500'],
            CourseWorkGradeBand::FAIL => ['label' => __('dashboard.academic_grade_fail'), 'color' => 'bg-rose-500'],
        ];

        $segments = [];

        foreach ($definitions as $key => $definition) {
            $count = $counts[$key];

            if ($count === 0) {
                continue;
            }

            $segments[] = [
                'key' => $key,
                'label' => $definition['label'],
                'count' => $count,
                'percent' => (int) round(($count / $total) * 100),
                'color' => $definition['color'],
            ];
        }

        return ['segments' => $segments];
    }

    /**
     * @param  list<array{band: string, departmentId: int, departmentName: string}>  $gradedResults
     * @return list<array<string, mixed>>
     */
    private function passRateByDepartment(array $gradedResults): array
    {
        return $this->passRateBreakdown($gradedResults, 'departmentId', 'departmentName');
    }

    /**
     * @param  list<array{band: string, levelId: int, levelName: string}>  $gradedResults
     * @return list<array<string, mixed>>
     */
    private function passRateByLevel(array $gradedResults): array
    {
        return $this->passRateBreakdown($gradedResults, 'levelId', 'levelName', 'level');
    }

    /**
     * @param  list<array{band: string, courseId: int, courseName: string}>  $gradedResults
     * @return list<array<string, mixed>>
     */
    private function passRateByCourse(array $gradedResults): array
    {
        return $this->passRateBreakdown($gradedResults, 'courseId', 'courseName', 'course');
    }

    /**
     * @param  list<array{band: string}>  $gradedResults
     * @return list<array<string, mixed>>
     */
    private function passRateBreakdown(array $gradedResults, string $idKey, string $nameKey, ?string $prefix = 'department'): array
    {
        $idField = $prefix === 'department' ? 'departmentId' : $idKey;
        $nameField = $prefix === 'department' ? 'departmentName' : $nameKey;
        $idResponseKey = $prefix === 'department' ? 'departmentId' : $idKey;
        $nameResponseKey = $prefix === 'department' ? 'departmentName' : $nameKey;

        $byGroup = collect($gradedResults)
            ->groupBy($idField)
            ->map(function (Collection $rows, int $groupId) use ($idResponseKey, $nameResponseKey, $nameField): array {
                $total = $rows->count();
                $passCount = $rows->filter(
                    fn (array $row): bool => CourseWorkGradeBand::isPassing($row['band'])
                )->count();

                return [
                    $idResponseKey => $groupId,
                    $nameResponseKey => (string) $rows->first()[$nameField],
                    'passRate' => $total > 0 ? (int) round(($passCount / $total) * 100) : 0,
                    'barPercent' => 0,
                ];
            })
            ->values()
            ->all();

        $maxRate = collect($byGroup)->max('passRate') ?? 0;

        if ($maxRate > 0) {
            $byGroup = array_map(function (array $row) use ($maxRate): array {
                $row['barPercent'] = (int) round(($row['passRate'] / $maxRate) * 100);

                return $row;
            }, $byGroup);
        }

        usort($byGroup, fn (array $left, array $right): int => $right['passRate'] <=> $left['passRate']);

        return $byGroup;
    }

    /**
     * @param  list<array{band: string, moduleId: int, moduleName: string}>  $gradedResults
     * @return list<array<string, mixed>>
     */
    private function moduleFailureHotspots(array $gradedResults): array
    {
        return collect($gradedResults)
            ->groupBy('moduleId')
            ->map(function (Collection $rows, int $moduleId): array {
                $enrolled = $rows->count();
                $failing = $rows->filter(
                    fn (array $row): bool => $row['band'] === CourseWorkGradeBand::FAIL
                )->count();

                return [
                    'moduleId' => $moduleId,
                    'moduleName' => (string) $rows->first()['moduleName'],
                    'enrolled' => $enrolled,
                    'failing' => $failing,
                    'rate' => $enrolled > 0 ? (int) round(($failing / $enrolled) * 100) : 0,
                ];
            })
            ->sortByDesc('rate')
            ->take(7)
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $moduleResults
     * @return list<array<string, mixed>>
     */
    private function missingMarksBreakdown(array $moduleResults, string $idKey, string $nameKey): array
    {
        return collect($moduleResults)
            ->groupBy($idKey)
            ->map(function (Collection $rows, int $groupId) use ($idKey, $nameKey): array {
                $expected = $rows->count();
                $incomplete = $rows->filter(fn (array $row): bool => ! $row['isComplete'])->count();

                return [
                    $idKey => $groupId,
                    $nameKey => (string) $rows->first()[$nameKey],
                    'expected' => $expected,
                    'incomplete' => $incomplete,
                    'rate' => $expected > 0 ? (int) round(($incomplete / $expected) * 100) : 0,
                ];
            })
            ->sortByDesc('rate')
            ->take(7)
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $moduleResults
     * @return list<array<string, mixed>>
     */
    private function lecturerMarkingStats(array $moduleResults): array
    {
        return collect($moduleResults)
            ->filter(fn (array $row): bool => $row['lecturerStaffId'] !== null)
            ->groupBy('lecturerStaffId')
            ->map(function (Collection $rows, int $staffId): array {
                $expected = $rows->count();
                $incomplete = $rows->filter(fn (array $row): bool => ! $row['isComplete'])->count();
                $completeRows = $rows->filter(fn (array $row): bool => $row['isComplete'] && $row['band'] !== null);
                $failCount = $completeRows->filter(
                    fn (array $row): bool => $row['band'] === CourseWorkGradeBand::FAIL
                )->count();
                $classesCount = $rows->pluck('academicCalendarClassId')->unique()->count();

                return [
                    'staffId' => $staffId,
                    'lecturerName' => (string) $rows->first()['lecturerName'],
                    'classesCount' => $classesCount,
                    'expected' => $expected,
                    'incomplete' => $incomplete,
                    'incompleteRate' => $expected > 0 ? (int) round(($incomplete / $expected) * 100) : 0,
                    'failRate' => $completeRows->count() > 0
                        ? (int) round(($failCount / $completeRows->count()) * 100)
                        : 0,
                ];
            })
            ->sortByDesc('incomplete')
            ->take(7)
            ->values()
            ->all();
    }

    /**
     * @return list<array{band: string, studentEnrolmentId: int, departmentId: int, departmentName: string, levelId: int, levelName: string, courseId: int, courseName: string, moduleId: int, moduleName: string}>
     */
    private function gradedResults(): array
    {
        return $this->gradedResultsFromModuleResults($this->moduleResults());
    }

    /**
     * @param  list<array<string, mixed>>  $moduleResults
     * @return list<array{band: string, studentEnrolmentId: int, departmentId: int, departmentName: string, levelId: int, levelName: string, courseId: int, courseName: string, moduleId: int, moduleName: string}>
     */
    private function gradedResultsFromModuleResults(array $moduleResults): array
    {
        $graded = [];

        foreach ($moduleResults as $row) {
            if (! $row['isComplete'] || $row['band'] === null) {
                continue;
            }

            $graded[] = [
                'band' => $row['band'],
                'studentEnrolmentId' => $row['studentEnrolmentId'],
                'departmentId' => $row['departmentId'],
                'departmentName' => $row['departmentName'],
                'levelId' => $row['levelId'],
                'levelName' => $row['levelName'],
                'courseId' => $row['courseId'],
                'courseName' => $row['courseName'],
                'moduleId' => $row['moduleId'],
                'moduleName' => $row['moduleName'],
            ];
        }

        return $graded;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function moduleResults(): array
    {
        if ($this->cachedModuleResults !== null) {
            return $this->cachedModuleResults;
        }

        $academicCalendar = Helper::resolveAcademicCalendar();

        $enrolments = $this->enrolmentsBaseQuery()
            ->where('academic_calendar_id', $academicCalendar->id)
            ->whereHas(
                'modeOfStudy',
                fn (Builder $query): Builder => $query->where('name', '!=', ModeOfStudyEnum::OJET->value),
            )
            ->with([
                'institutionDepartment.department',
                'departmentLevel.level',
                'departmentCourse.course',
                'academicCalendarStudentEnrolment.academicCalendarClass',
            ])
            ->get();

        if ($enrolments->isEmpty()) {
            return $this->cachedModuleResults = [];
        }

        $classIds = $enrolments
            ->map(fn (StudentEnrolment $enrolment): ?int => $enrolment->academicCalendarStudentEnrolment?->academic_calendar_class_id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->preloadLecturersByClassId($classIds);

        $enrolmentIds = $enrolments->pluck('id')->all();

        $marks = CourseWorkMark::query()
            ->whereIn('student_enrolment_id', $enrolmentIds)
            ->get()
            ->groupBy(fn (CourseWorkMark $mark): string => $mark->student_enrolment_id.'-'.$mark->course_syllabus_module_id);

        $results = [];

        foreach ($enrolments as $enrolment) {
            $classAssignment = $enrolment->academicCalendarStudentEnrolment;

            if ($classAssignment === null) {
                continue;
            }

            $class = $classAssignment->academicCalendarClass;

            if ($class === null) {
                continue;
            }

            $classConfig = ClassConfig::query()
                ->with(['departmentCourse.course', 'departmentLevel.level', 'institutionDepartment.department'])
                ->find($class->class_config_id);

            if ($classConfig === null) {
                continue;
            }

            $modules = $this->modulesForClassConfig($classConfig);
            $assessmentTypes = $this->assessmentTypesForMode((int) $enrolment->mode_of_study_id);

            if ($assessmentTypes === [] || $modules === []) {
                continue;
            }

            $department = $enrolment->institutionDepartment?->department;
            $level = $enrolment->departmentLevel?->level;
            $course = $enrolment->departmentCourse?->course;
            $lecturer = $this->lecturerByClassId[(int) $class->id] ?? null;

            foreach ($modules as $module) {
                $markKey = $enrolment->id.'-'.$module->id;
                $markGroup = $marks->get($markKey, collect());
                $hasNoMarks = $markGroup->isEmpty() || $markGroup->every(fn (CourseWorkMark $mark): bool => $mark->mark === null);

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
                    'departmentId' => (int) ($department?->id ?? 0),
                    'departmentName' => (string) ($department?->name ?? __('dashboard.academic_unknown_department')),
                    'levelId' => (int) ($level?->id ?? 0),
                    'levelName' => (string) ($level?->name ?? __('dashboard.academic_unknown_level')),
                    'courseId' => (int) ($course?->id ?? 0),
                    'courseName' => (string) ($course?->name ?? __('dashboard.academic_unknown_course')),
                    'moduleId' => (int) $module->id,
                    'moduleName' => (string) $module->title,
                    'academicCalendarClassId' => (int) $class->id,
                    'className' => (string) $class->name,
                    'isComplete' => $aggregation['isComplete'],
                    'hasNoMarks' => $hasNoMarks,
                    'band' => $band,
                    'lecturerStaffId' => $lecturer['staffId'] ?? null,
                    'lecturerName' => $lecturer['lecturerName'] ?? null,
                ];
            }
        }

        return $this->cachedModuleResults = $results;
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
     * @param  list<int>  $classIds
     */
    private function preloadLecturersByClassId(array $classIds): void
    {
        if ($classIds === []) {
            return;
        }

        $lecturerTypeId = ClassMetaDataType::query()
            ->where('name', ClassMetaDataTypeEnum::LECTURER->value)
            ->value('id');

        if ($lecturerTypeId === null) {
            return;
        }

        $assignments = AcademicCalendarClassMetaData::query()
            ->whereIn('academic_calendar_class_id', $classIds)
            ->where('class_metadata_type_id', $lecturerTypeId)
            ->whereNotNull('staff_id')
            ->with(['staff.user'])
            ->get();

        foreach ($assignments as $assignment) {
            $staff = $assignment->staff;
            $user = $staff?->user;
            $name = $user !== null
                ? trim(sprintf('%s %s', (string) $user->first_name, (string) $user->last_name))
                : __('dashboard.academic_unknown_lecturer');

            $this->lecturerByClassId[(int) $assignment->academic_calendar_class_id] = [
                'staffId' => (int) $assignment->staff_id,
                'lecturerName' => $name,
            ];
        }
    }

    private function enrolmentsBaseQuery(): Builder
    {
        $query = StudentEnrolment::query();

        if ($this->isDepartmentUser) {
            $query->whereIn('institution_department_id', $this->userDepartments);
        }

        return $query;
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

    /**
     * Ojet mode students are treated as on industrial attachment (placed).
     *
     * @return array{total: int, placed: int, awaiting: int, exempt: int, calendarYear: string, segments: list<array<string, mixed>>}|null
     */
    private function attachmentStatus(): ?array
    {
        $academicCalendar = Helper::resolveAcademicCalendar();
        $calendarYear = (string) $academicCalendar->calendar_year;

        $semesterIds = AcademicCalendar::semestersForCalendarYear($calendarYear)
            ->pluck('id')
            ->all();

        if ($semesterIds === []) {
            return null;
        }

        $placed = (int) $this->enrolmentsBaseQuery()
            ->whereIn('academic_calendar_id', $semesterIds)
            ->whereHas(
                'modeOfStudy',
                fn (Builder $query): Builder => $query->where('name', ModeOfStudyEnum::OJET->value),
            )
            ->distinct('student_id')
            ->count('student_id');

        if ($placed === 0) {
            return null;
        }

        return [
            'total' => $placed,
            'placed' => $placed,
            'awaiting' => 0,
            'exempt' => 0,
            'calendarYear' => $calendarYear,
            'segments' => [
                [
                    'key' => 'placed',
                    'label' => __('dashboard.academic_attachment_placed'),
                    'count' => $placed,
                    'percent' => 100,
                    'color' => 'bg-emerald-500',
                ],
            ],
        ];
    }
}
