<?php

namespace App\Services\AcademicCalendars;

use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Users\User;
use App\Support\AcademicCalendars\CourseWorkTemplateSignature;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CourseWorkImportTemplateService
{
    public function __construct(
        private readonly CourseWorkTreeService $treeService,
        private readonly CourseWorkMarkService $markService,
        private readonly CourseWorkAssessmentLockService $lockService,
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
     *     layout: string,
     *     header: array<string, mixed>,
     *     fileName: array{moduleTitle: string, moduleCode: string, level: string, mode: string},
     *     assessmentTypes: list<array{id: int, name: string, weightPercent: int|null}>,
     *     rows: list<array<string, mixed>>,
     *     editableAssessmentTypeIds: list<int>,
     *     markEditable: bool,
     *     closedAssessments: list<array{id: int|null, name: string, message: string}>,
     *     readOnlyMessage: string|null,
     *     signature: array{payload: string, signature: string}
     * }
     */
    public function assembleForClassConfig(int $classConfigId, int $courseSyllabusModuleId, ?int $academicCalendarClassId = null): array
    {
        $classConfig = $this->markService->assertClassConfigExists($classConfigId);
        $classConfig->loadMissing([
            'departmentCourse.course',
            'departmentLevel.level',
            'modeOfStudy',
            'institutionDepartment.department',
            'semester',
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
        $captureMarkOnly = (bool) $module->capture_mark_only;

        $studentEnrolmentIds = collect($modulePayload['students'])
            ->pluck('studentEnrolmentId')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $savedMarksByKey = $studentEnrolmentIds === []
            ? collect()
            : CourseWorkMark::query()
                ->where('course_syllabus_module_id', $courseSyllabusModuleId)
                ->whereIn('student_enrolment_id', $studentEnrolmentIds)
                ->when(
                    $captureMarkOnly,
                    fn ($query) => $query->whereNull('assessment_type_id'),
                    fn ($query) => $query->whereNotNull('assessment_type_id'),
                )
                ->get()
                ->groupBy(fn (CourseWorkMark $mark): string => $captureMarkOnly
                    ? sprintf('%d', (int) $mark->student_enrolment_id)
                    : sprintf('%d:%d', (int) $mark->student_enrolment_id, (int) $mark->assessment_type_id));

        $rows = [];

        foreach ($modulePayload['students'] as $student) {
            $studentEnrolmentId = (int) $student['studentEnrolmentId'];

            if ($captureMarkOnly) {
                $saved = $savedMarksByKey->get((string) $studentEnrolmentId)?->first();
                $rows[] = [
                    'studentEnrolmentId' => $student['studentEnrolmentId'],
                    'studentNumber' => $student['studentNumber'],
                    'studentName' => $student['name'],
                    'className' => $student['className'] ?? null,
                    'mark' => $saved?->mark !== null ? (int) $saved->mark : null,
                    'remark' => $saved?->remark,
                ];

                continue;
            }

            $marks = [];

            foreach ($assessmentTypes as $type) {
                $typeId = (int) $type['id'];
                $saved = $savedMarksByKey
                    ->get(sprintf('%d:%d', $studentEnrolmentId, $typeId))
                    ?->first();

                $marks[$typeId] = $saved?->mark !== null ? (int) $saved->mark : null;
            }

            $rows[] = [
                'studentEnrolmentId' => $student['studentEnrolmentId'],
                'studentNumber' => $student['studentNumber'],
                'studentName' => $student['name'],
                'className' => $student['className'] ?? null,
                'marks' => $marks,
            ];
        }

        $lock = $this->lockFor($classConfig, $module, $academicCalendarClassId);
        $lockedAssessmentTypeIds = array_map('intval', $lock['lockedAssessmentTypeIds'] ?? []);
        $assessmentTypeIds = array_map(static fn (array $type): int => (int) $type['id'], $assessmentTypes);
        $editableAssessmentTypeIds = array_values(array_filter(
            $assessmentTypeIds,
            static fn (int $id): bool => ! in_array($id, $lockedAssessmentTypeIds, true),
        ));
        $layout = $captureMarkOnly ? 'mark_only' : 'wide';
        $user = Auth::user();

        return [
            'layout' => $layout,
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
            'editableAssessmentTypeIds' => $editableAssessmentTypeIds,
            'markEditable' => (bool) ($lock['hasEditableCourseWork'] ?? true),
            'closedAssessments' => collect($lock['windows'] ?? [])
                ->filter(fn (array $window): bool => ! ($window['captureAllowed'] ?? true))
                ->map(fn (array $window): array => [
                    'id' => $window['assessmentTypeId'],
                    'name' => (string) $window['assessmentTypeName'],
                    'message' => (string) $window['message'],
                ])
                ->values()
                ->all(),
            'readOnlyMessage' => $lock['readOnlyMessage'] ?? null,
            'signature' => CourseWorkTemplateSignature::sign([
                'tenantId' => $user instanceof User && $user->tenant_id !== null ? (int) $user->tenant_id : null,
                'userId' => $user instanceof User ? (int) $user->id : null,
                'classConfigId' => $classConfigId,
                'classId' => $academicCalendarClassId,
                'moduleId' => $courseSyllabusModuleId,
                'layout' => $layout,
                'typeColumnOrder' => $captureMarkOnly ? [] : $assessmentTypeIds,
                'editableAssessmentTypeIds' => $editableAssessmentTypeIds,
                'students' => collect($rows)
                    ->mapWithKeys(fn (array $row): array => [
                        (string) (int) $row['studentEnrolmentId'] => (string) ($row['studentNumber'] ?? ''),
                    ])
                    ->all(),
                'generatedAt' => now()->toIso8601String(),
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function lockFor(ClassConfig $classConfig, CourseSyllabusModule $module, ?int $academicCalendarClassId): array
    {
        if ($academicCalendarClassId !== null) {
            $class = AcademicCalendarClass::query()->find($academicCalendarClassId);

            if ($class instanceof AcademicCalendarClass) {
                return $this->lockService->locksForClassAndModules($class, [$module])[(int) $module->id] ?? [];
            }
        }

        return $this->lockService->locksForClassConfigAndModules($classConfig, [$module])[(int) $module->id] ?? [];
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
