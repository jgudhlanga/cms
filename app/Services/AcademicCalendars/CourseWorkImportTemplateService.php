<?php

namespace App\Services\AcademicCalendars;

use App\Models\Institution\Syllabus\CourseSyllabusModule;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CourseWorkImportTemplateService
{
    public function __construct(
        private readonly CourseWorkTreeService $treeService,
        private readonly CourseWorkMarkService $markService,
    ) {}

    /**
     * @param  array{fileName: array{moduleTitle: string, moduleCode: string, level: string, mode: string}}  $data
     */
    public function downloadFileName(array $data): string
    {
        $segments = $data['fileName'];

        return strtoupper(sprintf(
            '%s-%s-%s-%s-course-work-%s.xlsx',
            $segments['moduleTitle'],
            $segments['moduleCode'],
            $segments['level'],
            $segments['mode'],
            time(),
        ));
    }

    /**
     * @return array{
     *     header: array<string, mixed>,
     *     fileName: array{moduleTitle: string, moduleCode: string, level: string, mode: string},
     *     assessmentTypes: list<array{id: int, name: string, weightPercent: int|null}>,
     *     rows: list<array<string, mixed>>
     * }
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
        $modulePayload = $this->findModuleInTree($tree, $courseSyllabusModuleId);

        if ($modulePayload === null) {
            throw ValidationException::withMessages([
                'courseSyllabusModuleId' => [__('academic_calendar.course_work_module_not_in_class')],
            ]);
        }

        /** @var list<array{id: int, name: string, weightPercent: int|null}> $assessmentTypes */
        $assessmentTypes = array_values($tree['assessmentTypes'] ?? []);
        $rows = [];

        foreach ($modulePayload['students'] as $student) {
            $marks = [];

            foreach ($assessmentTypes as $type) {
                $marks[(int) $type['id']] = null;
            }

            $rows[] = [
                'studentEnrolmentId' => $student['studentEnrolmentId'],
                'studentNumber' => $student['studentNumber'],
                'studentName' => $student['name'],
                'className' => $student['className'] ?? null,
                'marks' => $marks,
            ];
        }

        return [
            'header' => [
                'moduleCode' => $module->code,
                'moduleTitle' => $module->title,
                'course' => $classConfig->departmentCourse?->course?->name,
                'level' => $classConfig->departmentLevel?->level?->name,
                'modeOfStudy' => $classConfig->modeOfStudy?->name,
                'calendarYear' => $classConfig->calendar_year,
                'generatedAt' => now()->format('d M Y'),
            ],
            'fileName' => [
                'moduleTitle' => Str::slug((string) $module->title) ?: 'module',
                'moduleCode' => Str::slug((string) $module->code) ?: 'module',
                'level' => Str::slug((string) $classConfig->departmentLevel?->level?->name) ?: 'level',
                'mode' => Str::slug((string) $classConfig->modeOfStudy?->name) ?: 'mode',
            ],
            'assessmentTypes' => $assessmentTypes,
            'rows' => $rows,
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
}
