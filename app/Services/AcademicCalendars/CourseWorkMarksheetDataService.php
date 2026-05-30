<?php

namespace App\Services\AcademicCalendars;

use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use Illuminate\Validation\ValidationException;

class CourseWorkMarksheetDataService
{
    public function __construct(
        private readonly CourseWorkTreeService $treeService,
        private readonly CourseWorkAggregationService $aggregationService,
        private readonly CourseWorkMarkService $markService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function assembleForClassConfig(int $classConfigId, int $courseSyllabusModuleId): array
    {
        $classConfig = $this->markService->assertClassConfigExists($classConfigId);
        $classConfig->loadMissing([
            'departmentCourse.course',
            'departmentLevel.level',
            'modeOfStudy',
            'institutionDepartment.department',
            'academicYearOption',
        ]);

        $module = CourseSyllabusModule::query()->find($courseSyllabusModuleId);
        if ($module === null) {
            throw ValidationException::withMessages([
                'courseSyllabusModuleId' => [__('academic_calendar.course_work_module_not_found')],
            ]);
        }

        $tree = $this->treeService->buildForClassConfig($classConfigId);

        return $this->assembleFromTree($tree, $courseSyllabusModuleId, $classConfig, $module, null);
    }

    /**
     * @return array<string, mixed>
     */
    public function assemble(int $academicCalendarClassId, int $courseSyllabusModuleId): array
    {
        $academicCalendarClass = $this->markService->assertClassExists($academicCalendarClassId);
        $academicCalendarClass->loadMissing([
            'classConfig.departmentCourse.course',
            'classConfig.departmentLevel.level',
            'classConfig.modeOfStudy',
            'classConfig.institutionDepartment.department',
            'classConfig.academicYearOption',
        ]);

        $classConfig = $academicCalendarClass->classConfig;
        if (! $classConfig instanceof ClassConfig) {
            throw ValidationException::withMessages([
                'academicCalendarClassId' => [__('academic_calendar.course_work_class_not_found')],
            ]);
        }

        $module = CourseSyllabusModule::query()->find($courseSyllabusModuleId);
        if ($module === null) {
            throw ValidationException::withMessages([
                'courseSyllabusModuleId' => [__('academic_calendar.course_work_module_not_found')],
            ]);
        }

        $tree = $this->treeService->buildForClass($academicCalendarClassId);

        return $this->assembleFromTree($tree, $courseSyllabusModuleId, $classConfig, $module, $academicCalendarClass);
    }

    /**
     * @param  array<string, mixed>  $tree
     * @return array<string, mixed>
     */
    private function assembleFromTree(
        array $tree,
        int $courseSyllabusModuleId,
        ClassConfig $classConfig,
        CourseSyllabusModule $module,
        ?AcademicCalendarClass $academicCalendarClass,
    ): array {
        $assessmentTypes = $tree['assessmentTypes'] ?? [];

        if ($assessmentTypes === []) {
            throw ValidationException::withMessages([
                'academicCalendarClassId' => [__('academic_calendar.course_work_no_assessment_types')],
            ]);
        }

        $modulePayload = $this->findModuleInTree($tree, $courseSyllabusModuleId);
        if ($modulePayload === null) {
            throw ValidationException::withMessages([
                'courseSyllabusModuleId' => [__('academic_calendar.course_work_module_not_in_class')],
            ]);
        }

        $rows = [];
        $issues = [];

        foreach ($modulePayload['students'] as $student) {
            $assessments = $student['assessments'] ?? [];
            $aggregation = $this->aggregationService->aggregateStudentModule($assessmentTypes, $assessments);

            $candidateNumber = $student['studentNumber'] ?? null;
            if ($candidateNumber === null || trim((string) $candidateNumber) === '') {
                $issues[] = [
                    'studentEnrolmentId' => $student['studentEnrolmentId'],
                    'studentName' => $student['name'],
                    'issue' => __('academic_calendar.course_work_export_issue_missing_candidate_number'),
                ];
            }

            if (! $aggregation['isComplete']) {
                $issues[] = [
                    'studentEnrolmentId' => $student['studentEnrolmentId'],
                    'studentName' => $student['name'],
                    'issue' => __('academic_calendar.course_work_export_issue_incomplete_marks'),
                ];
            }

            $componentMarks = [];
            foreach ($aggregation['components'] as $component) {
                $componentMarks[$component['assessmentTypeId']] = $component;
            }

            $rows[] = [
                'studentEnrolmentId' => $student['studentEnrolmentId'],
                'candidateNumber' => $candidateNumber,
                'name' => $student['name'],
                'components' => array_values($aggregation['components']),
                'componentMarksByTypeId' => $componentMarks,
                'courseWorkTotal60' => $aggregation['courseWorkTotal60'],
                'isComplete' => $aggregation['isComplete'],
                'remark' => $aggregation['remark'],
            ];
        }

        $completeCount = collect($rows)->where('isComplete', true)->count();

        return [
            'header' => $this->buildHeader($classConfig, $module, $academicCalendarClass),
            'assessmentTypes' => $assessmentTypes,
            'rows' => $rows,
            'summary' => [
                'studentCount' => count($rows),
                'completeCount' => $completeCount,
            ],
            'issues' => $issues,
        ];
    }

    /**
     * @param  array<string, mixed>  $tree
     * @return array<string, mixed>|null
     */
    private function findModuleInTree(array $tree, int $moduleId): ?array
    {
        foreach ($tree['syllabi'] ?? [] as $syllabus) {
            foreach ($syllabus['modules'] ?? [] as $module) {
                if ((int) ($module['id'] ?? 0) === $moduleId) {
                    return $module;
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildHeader(
        ClassConfig $classConfig,
        CourseSyllabusModule $module,
        ?AcademicCalendarClass $academicCalendarClass,
    ): array {
        $department = $classConfig->institutionDepartment?->department?->name;
        $course = $classConfig->departmentCourse?->course?->name;
        $level = $classConfig->departmentLevel?->level?->name;

        return [
            'centre' => 'Harare Polytechnic',
            'centreNumber' => '001',
            'coordinatingCollege' => 'Harare Polytechnic',
            'level' => $level,
            'discipline' => $department,
            'courseCode' => null,
            'course' => $course,
            'subjectCode' => $module->code,
            'subject' => $module->title,
            'className' => $academicCalendarClass?->name,
            'session' => sprintf('%02d/%s', now()->month, $classConfig->calendar_year),
            'modeOfStudy' => $classConfig->modeOfStudy?->name,
            'academicYearOption' => $classConfig->academicYearOption?->name,
            'generatedAt' => now()->format('d M Y'),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $issues
     */
    public function assertExportable(array $issues, bool $strict = false): void
    {
        if ($strict && $issues !== []) {
            throw ValidationException::withMessages([
                'export' => [__('academic_calendar.course_work_export_blocked')],
            ]);
        }
    }
}
