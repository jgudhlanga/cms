<?php

namespace App\Services\Lecturer;

use App\Helpers\Helper;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Users\User;
use App\Services\AcademicCalendars\ClassStaffingService;
use App\Services\Assessments\AssessmentCalendarWindowService;
use App\Services\Dashboard\LecturerDashboardMetricsService;
use App\Support\AcademicCalendars\CourseWorkGradeBand;
use Illuminate\Support\Collection;

class LecturerTeachingClassesIndexService
{
    public function __construct(
        private readonly LecturerAssignmentResolver $assignmentResolver,
        private readonly ClassStaffingService $classStaffingService,
        private readonly LecturerDashboardMetricsService $dashboardMetricsService,
        private readonly AssessmentCalendarWindowService $assessmentCalendarWindowService,
    ) {}

    /**
     * @return array{
     *     classes: list<array<string, mixed>>,
     *     summary: array<string, int>
     * }
     */
    public function build(User $user): array
    {
        $resolved = $this->assignmentResolver->resolveForUser($user);

        if ($resolved['classIds'] === []) {
            return [
                'classes' => [],
                'summary' => $this->emptySummary(),
            ];
        }

        $academicCalendar = Helper::resolveAcademicCalendar();
        $calendarYear = (string) $academicCalendar->calendar_year;

        /** @var Collection<int, AcademicCalendarClass> $classes */
        $classes = AcademicCalendarClass::query()
            ->whereIn('id', $resolved['classIds'])
            ->with([
                'classConfig.institutionDepartment.department',
                'classConfig.departmentCourse.course',
                'classConfig.departmentLevel.level',
                'classConfig.modeOfStudy',
            ])
            ->orderBy('name')
            ->get()
            ->filter(function (AcademicCalendarClass $class) use ($calendarYear): bool {
                return (string) ($class->classConfig?->calendar_year ?? '') === $calendarYear;
            });

        if ($classes->isEmpty()) {
            return [
                'classes' => [],
                'summary' => $this->emptySummary(),
            ];
        }

        $classIds = $classes->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $previewsByClassId = $this->classStaffingService->classPreviewsByClassId($classIds);
        $tutorsByClassId = $this->classStaffingService->tutorsByClassId($classIds);
        $semesterModulesByClassId = $this->semesterModulesByClassId($classes);
        $assignedModuleIdsByClassId = $this->assignedModuleIdsByClassId($resolved['assignmentKeys']);
        $moduleCodeById = $this->moduleCodeById($semesterModulesByClassId, $assignedModuleIdsByClassId);
        $assessmentWindows = $this->assessmentCalendarWindowService->windowsForAcademicCalendar((int) $academicCalendar->id);
        $statsByClassId = $this->statsByClassId(
            $this->dashboardMetricsService->moduleResultsForResolved($resolved),
        );

        $cards = $classes
            ->map(function (AcademicCalendarClass $class) use (
                $resolved,
                $previewsByClassId,
                $tutorsByClassId,
                $semesterModulesByClassId,
                $assignedModuleIdsByClassId,
                $moduleCodeById,
                $assessmentWindows,
                $statsByClassId,
            ): array {
                $classId = (int) $class->id;
                $config = $class->classConfig;
                $isTutor = in_array($classId, $resolved['tutorClassIds'], true);
                $preview = $previewsByClassId[$classId] ?? [
                    'academicCalendarClassId' => $classId,
                    'name' => (string) $class->name,
                    'studentCount' => 0,
                    'genderCounts' => ['male' => 0, 'female' => 0, 'unknown' => 0],
                    'students' => [],
                ];

                $assignedModuleIds = $assignedModuleIdsByClassId[$classId] ?? [];
                $assignedModuleCodes = $this->codesForModuleIds($assignedModuleIds, $moduleCodeById);
                $semesterModuleCodes = $this->codesForModules($semesterModulesByClassId[$classId] ?? collect());
                $moduleCodes = $isTutor ? $semesterModuleCodes : $assignedModuleCodes;
                $modeOfStudyId = (int) ($config?->mode_of_study_id ?? 0);

                return [
                    ...$preview,
                    'tutor' => $this->classStaffingService->formatTutorSummary($tutorsByClassId[$classId] ?? null),
                    'isTutor' => $isTutor,
                    'departmentName' => (string) ($config?->institutionDepartment?->department?->name ?? ''),
                    'courseName' => (string) ($config?->departmentCourse?->course?->name ?? ''),
                    'levelName' => (string) ($config?->departmentLevel?->level?->name ?? ''),
                    'modeOfStudyName' => (string) ($config?->modeOfStudy?->name ?? ''),
                    'calendarYear' => (string) ($config?->calendar_year ?? ''),
                    'moduleCodes' => $moduleCodes,
                    'assignedModuleCodes' => $assignedModuleCodes,
                    'assessmentWindows' => $this->assessmentCalendarWindowService->windowsForClass(
                        $assessmentWindows,
                        $classId,
                        $modeOfStudyId,
                    ),
                    'stats' => $statsByClassId[$classId] ?? $this->emptyClassStats(count($assignedModuleIds)),
                ];
            })
            ->values()
            ->all();

        return [
            'classes' => $cards,
            'summary' => $this->buildSummary($cards),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function emptySummary(): array
    {
        return [
            'classCount' => 0,
            'studentCount' => 0,
            'assignedModuleCount' => 0,
            'openAssessmentWindowCount' => 0,
            'missingCourseWorkCount' => 0,
        ];
    }

    /**
     * @return array<string, int|float|null>
     */
    private function emptyClassStats(int $assignedModuleCount = 0): array
    {
        return [
            'assignedModuleCount' => $assignedModuleCount,
            'missingCourseWorkCount' => 0,
            'passRate' => null,
            'averageMark' => null,
        ];
    }

    /**
     * @param  Collection<int, AcademicCalendarClass>  $classes
     * @return array<int, Collection<int, CourseSyllabusModule>>
     */
    private function semesterModulesByClassId(Collection $classes): array
    {
        $modulesByClassConfigId = [];
        $semesterModulesByClassId = [];

        foreach ($classes as $class) {
            $classId = (int) $class->id;
            $classConfig = $class->classConfig;

            if (! $classConfig instanceof ClassConfig) {
                $semesterModulesByClassId[$classId] = collect();

                continue;
            }

            $classConfigId = (int) $classConfig->id;

            if (! isset($modulesByClassConfigId[$classConfigId])) {
                $modulesByClassConfigId[$classConfigId] = $this->classStaffingService->resolveSemesterModules($classConfig);
            }

            $semesterModulesByClassId[$classId] = $modulesByClassConfigId[$classConfigId];
        }

        return $semesterModulesByClassId;
    }

    /**
     * @param  list<string>  $assignmentKeys
     * @return array<int, list<int>>
     */
    private function assignedModuleIdsByClassId(array $assignmentKeys): array
    {
        $assigned = [];

        foreach ($assignmentKeys as $key) {
            [$classId, $moduleId] = array_map('intval', explode('-', $key, 2));
            $assigned[$classId][] = $moduleId;
        }

        foreach ($assigned as $classId => $moduleIds) {
            $assigned[$classId] = array_values(array_unique($moduleIds));
        }

        return $assigned;
    }

    /**
     * @param  array<int, Collection<int, CourseSyllabusModule>>  $semesterModulesByClassId
     * @param  array<int, list<int>>  $assignedModuleIdsByClassId
     * @return array<int, string>
     */
    private function moduleCodeById(array $semesterModulesByClassId, array $assignedModuleIdsByClassId): array
    {
        $moduleIds = [];

        foreach ($semesterModulesByClassId as $modules) {
            foreach ($modules as $module) {
                $moduleIds[] = (int) $module->id;
            }
        }

        foreach ($assignedModuleIdsByClassId as $ids) {
            foreach ($ids as $moduleId) {
                $moduleIds[] = (int) $moduleId;
            }
        }

        $moduleIds = array_values(array_unique($moduleIds));

        if ($moduleIds === []) {
            return [];
        }

        return CourseSyllabusModule::query()
            ->whereIn('id', $moduleIds)
            ->pluck('code', 'id')
            ->mapWithKeys(fn (mixed $code, mixed $id): array => [(int) $id => (string) $code])
            ->all();
    }

    /**
     * @param  list<int>  $moduleIds
     * @param  array<int, string>  $moduleCodeById
     * @return list<string>
     */
    private function codesForModuleIds(array $moduleIds, array $moduleCodeById): array
    {
        $codes = [];

        foreach ($moduleIds as $moduleId) {
            $code = trim((string) ($moduleCodeById[$moduleId] ?? ''));

            if ($code !== '') {
                $codes[] = $code;
            }
        }

        sort($codes);

        return array_values(array_unique($codes));
    }

    /**
     * @param  Collection<int, CourseSyllabusModule>  $modules
     * @return list<string>
     */
    private function codesForModules(Collection $modules): array
    {
        return $modules
            ->map(fn (CourseSyllabusModule $module): string => trim((string) ($module->code ?? '')))
            ->filter(fn (string $code): bool => $code !== '')
            ->sort()
            ->values()
            ->unique()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $moduleResults
     * @return array<int, array<string, int|float|null>>
     */
    private function statsByClassId(array $moduleResults): array
    {
        $grouped = [];

        foreach ($moduleResults as $row) {
            $classId = (int) $row['academicCalendarClassId'];
            $grouped[$classId][] = $row;
        }

        $statsByClassId = [];

        foreach ($grouped as $classId => $rows) {
            $graded = array_values(array_filter(
                $rows,
                fn (array $row): bool => $row['isComplete'] && $row['courseWorkTotal60'] !== null,
            ));
            $missingCourseWorkCount = count(array_filter(
                $rows,
                fn (array $row): bool => ! $row['isComplete'],
            ));
            $moduleIds = array_values(array_unique(array_map(
                fn (array $row): int => (int) $row['moduleId'],
                $rows,
            )));

            $passRate = null;
            $averageMark = null;

            if ($graded !== []) {
                $passCount = count(array_filter(
                    $graded,
                    fn (array $row): bool => $row['band'] !== null && CourseWorkGradeBand::isPassing((string) $row['band']),
                ));
                $passRate = round(($passCount / count($graded)) * 100, 1);
                $averageMark = round(
                    array_sum(array_map(fn (array $row): int => (int) $row['courseWorkTotal60'], $graded)) / count($graded),
                    1,
                );
            }

            $statsByClassId[$classId] = [
                'assignedModuleCount' => count($moduleIds),
                'missingCourseWorkCount' => $missingCourseWorkCount,
                'passRate' => $passRate,
                'averageMark' => $averageMark,
            ];
        }

        return $statsByClassId;
    }

    /**
     * @param  list<array<string, mixed>>  $cards
     * @return array<string, int>
     */
    private function buildSummary(array $cards): array
    {
        $assignedModuleCodes = [];
        $openWindowKeys = [];
        $studentCount = 0;
        $missingCourseWorkCount = 0;

        foreach ($cards as $card) {
            $studentCount += (int) ($card['studentCount'] ?? 0);
            $missingCourseWorkCount += (int) ($card['stats']['missingCourseWorkCount'] ?? 0);

            foreach ($card['assignedModuleCodes'] ?? [] as $code) {
                $assignedModuleCodes[(string) $code] = true;
            }

            foreach ($card['assessmentWindows'] ?? [] as $window) {
                if (($window['isOpen'] ?? false) !== true) {
                    continue;
                }

                $openWindowKeys[sprintf(
                    '%s|%s|%s',
                    (string) ($window['assessmentTypeName'] ?? ''),
                    (string) ($window['startDate'] ?? ''),
                    (string) ($window['endDate'] ?? ''),
                )] = true;
            }
        }

        return [
            'classCount' => count($cards),
            'studentCount' => $studentCount,
            'assignedModuleCount' => count($assignedModuleCodes),
            'openAssessmentWindowCount' => count($openWindowKeys),
            'missingCourseWorkCount' => $missingCourseWorkCount,
        ];
    }
}
