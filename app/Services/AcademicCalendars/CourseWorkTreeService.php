<?php

namespace App\Services\AcademicCalendars;

use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Institution\AssessmentType;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Support\Institution\CourseSyllabusModulePeriod;
use Illuminate\Support\Collection;

class CourseWorkTreeService
{
    public function __construct(
        private readonly CourseWorkMarkService $markService,
        private readonly CourseWorkAggregationService $aggregationService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildForClass(int $academicCalendarClassId): array
    {
        $academicCalendarClass = $this->markService->assertClassExists($academicCalendarClassId);
        $academicCalendarClass->loadMissing(['classConfig.departmentCourse.course']);

        $classConfig = $academicCalendarClass->classConfig;

        if (! $classConfig instanceof ClassConfig) {
            return [
                'academicCalendarClassId' => $academicCalendarClassId,
                'syllabi' => [],
                'assessmentTypes' => [],
                'students' => [],
                'marksheetSummary' => [],
            ];
        }

        $students = $this->studentsForClass($academicCalendarClassId);

        return $this->buildTreeForClassConfig($classConfig, $students, [
            'academicCalendarClassId' => $academicCalendarClassId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildForClassConfig(int $classConfigId): array
    {
        $classConfig = $this->markService->assertClassConfigExists($classConfigId);
        $classConfig->loadMissing(['departmentCourse.course']);

        $students = $this->studentsForClassConfig($classConfigId);

        return $this->buildTreeForClassConfig($classConfig, $students, [
            'classConfigId' => $classConfigId,
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $students
     * @param  array<string, int>  $meta
     * @return array<string, mixed>
     */
    private function buildTreeForClassConfig(ClassConfig $classConfig, array $students, array $meta): array
    {
        $context = $this->resolveSyllabusContext($classConfig);

        if ($context === null) {
            return [
                ...$meta,
                'modeOfStudyId' => (int) $classConfig->mode_of_study_id,
                'syllabi' => [],
                'assessmentTypes' => [],
                'students' => $students,
                'marksheetSummary' => [],
            ];
        }

        $studentEnrolmentIds = collect($students)->pluck('studentEnrolmentId')->map(fn ($id) => (int) $id)->all();

        $marks = $studentEnrolmentIds === []
            ? collect()
            : CourseWorkMark::query()
                ->whereIn('student_enrolment_id', $studentEnrolmentIds)
                ->get()
                ->groupBy(fn (CourseWorkMark $mark): string => $this->markKey(
                    (int) $mark->student_enrolment_id,
                    (int) $mark->course_syllabus_module_id,
                    $mark->assessment_type_id !== null ? (int) $mark->assessment_type_id : null,
                ));

        $includeClassFields = array_key_exists('classConfigId', $meta);

        $syllabiPayload = $this->buildSyllabiPayload(
            $context['syllabi'],
            $context['modulesBySyllabusId'],
            $students,
            $marks,
            $context['assessmentTypes'],
            $includeClassFields,
        );

        return [
            ...$meta,
            'modeOfStudyId' => (int) $classConfig->mode_of_study_id,
            'syllabi' => $syllabiPayload,
            'assessmentTypes' => $context['assessmentTypes'],
            'students' => $students,
            'marksheetSummary' => $this->buildMarksheetSummary($syllabiPayload),
        ];
    }

    /**
     * @return array{syllabi: Collection<int, CourseSyllabus>, modulesBySyllabusId: Collection, assessmentTypes: list<array<string, mixed>>}|null
     */
    private function resolveSyllabusContext(ClassConfig $classConfig): ?array
    {
        $syllabusIds = array_values(array_map(
            'intval',
            array_filter($classConfig->course_syllabus_ids ?? [])
        ));

        if ($syllabusIds === []) {
            return null;
        }

        $syllabi = CourseSyllabus::query()
            ->whereIn('id', $syllabusIds)
            ->orderBy('implementation_year')
            ->orderBy('code')
            ->get();

        $slugPrefix = $syllabusIds !== []
            ? CourseSyllabusModulePeriod::slugPrefixForSyllabus($syllabusIds[0])
            : 'semester';

        $modulesBySyllabusId = CourseSyllabusModule::query()
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
            ->groupBy('course_syllabus_id');

        $assessmentTypes = AssessmentType::query()
            ->whereJsonContains('modes_of_study', (int) $classConfig->mode_of_study_id)
            ->orderBy('name')
            ->get()
            ->map(fn (AssessmentType $type): array => [
                'id' => $type->id,
                'name' => $type->name,
                'description' => $type->description,
                'weightPercent' => $type->weight_percent,
            ])
            ->values()
            ->all();

        return [
            'syllabi' => $syllabi,
            'modulesBySyllabusId' => $modulesBySyllabusId,
            'assessmentTypes' => $assessmentTypes,
        ];
    }

    /**
     * @param  Collection<int, CourseSyllabus>  $syllabi
     * @param  Collection<int, Collection<int, CourseSyllabusModule>>  $modulesBySyllabusId
     * @param  list<array<string, mixed>>  $students
     * @param  Collection<string, Collection<int, CourseWorkMark>>  $marks
     * @param  list<array{id: int, name: string, description: string|null, weightPercent: int|null}>  $assessmentTypes
     * @return list<array<string, mixed>>
     */
    private function buildSyllabiPayload(
        Collection $syllabi,
        Collection $modulesBySyllabusId,
        array $students,
        Collection $marks,
        array $assessmentTypes,
        bool $includeClassFields,
    ): array {
        return $syllabi->map(function (CourseSyllabus $syllabus) use (
            $modulesBySyllabusId,
            $students,
            $marks,
            $assessmentTypes,
            $includeClassFields,
        ): array {
            $modules = $modulesBySyllabusId->get($syllabus->id, collect());

            return [
                'id' => $syllabus->id,
                'code' => $syllabus->code,
                'title' => $syllabus->title,
                'modules' => $modules->map(function (CourseSyllabusModule $module) use (
                    $students,
                    $marks,
                    $assessmentTypes,
                    $includeClassFields,
                ): array {
                    $modulePayload = [
                        'id' => $module->id,
                        'code' => $module->code,
                        'title' => $module->title,
                        'durationInHours' => $module->duration_in_hours,
                        'captureMarkOnly' => (bool) $module->capture_mark_only,
                    ];

                    $modulePayload['students'] = collect($students)->map(function (array $student) use (
                        $module,
                        $marks,
                        $assessmentTypes,
                        $includeClassFields,
                    ): array {
                        return $this->buildStudentRowForModule(
                            $student,
                            $module,
                            $marks,
                            $assessmentTypes,
                            $includeClassFields,
                        );
                    })->values()->all();

                    return $modulePayload;
                })->values()->all(),
            ];
        })->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function buildForStudent(int $academicCalendarClassId, int $studentEnrolmentId): array
    {
        $this->markService->assertEnrolmentBelongsToClass($studentEnrolmentId, $academicCalendarClassId);

        $academicCalendarClass = $this->markService->assertClassExists($academicCalendarClassId);
        $academicCalendarClass->loadMissing(['classConfig.departmentCourse.course']);

        $classConfig = $academicCalendarClass->classConfig;

        if (! $classConfig instanceof ClassConfig) {
            return [
                'academicCalendarClassId' => $academicCalendarClassId,
                'studentEnrolmentId' => $studentEnrolmentId,
                'student' => null,
                'syllabi' => [],
                'assessmentTypes' => [],
            ];
        }

        $student = collect($this->studentsForClass($academicCalendarClassId))
            ->firstWhere('studentEnrolmentId', $studentEnrolmentId);

        $context = $this->resolveSyllabusContext($classConfig);

        if ($context === null) {
            return [
                'academicCalendarClassId' => $academicCalendarClassId,
                'studentEnrolmentId' => $studentEnrolmentId,
                'student' => $student,
                'syllabi' => [],
                'assessmentTypes' => [],
            ];
        }

        $marks = CourseWorkMark::query()
            ->where('student_enrolment_id', $studentEnrolmentId)
            ->get()
            ->groupBy(fn (CourseWorkMark $mark): string => $this->markKey(
                (int) $mark->student_enrolment_id,
                (int) $mark->course_syllabus_module_id,
                $mark->assessment_type_id !== null ? (int) $mark->assessment_type_id : null,
            ));

        $syllabiPayload = $context['syllabi']->map(function (CourseSyllabus $syllabus) use (
            $context,
            $studentEnrolmentId,
            $marks,
        ): array {
            $modules = $context['modulesBySyllabusId']->get($syllabus->id, collect());

            return [
                'id' => $syllabus->id,
                'code' => $syllabus->code,
                'title' => $syllabus->title,
                'modules' => $modules->map(function (CourseSyllabusModule $module) use (
                    $studentEnrolmentId,
                    $marks,
                    $context,
                ): array {
                    $modulePayload = [
                        'id' => $module->id,
                        'code' => $module->code,
                        'title' => $module->title,
                        'durationInHours' => $module->duration_in_hours,
                        'captureMarkOnly' => (bool) $module->capture_mark_only,
                    ];

                    if ($module->capture_mark_only) {
                        $saved = $marks->get($this->markKey($studentEnrolmentId, (int) $module->id, null))?->first();
                        $modulePayload['moduleMark'] = $this->formatModuleMark($saved);
                        $modulePayload['assessments'] = [];
                    } else {
                        $assessments = collect($context['assessmentTypes'])->map(function (array $type) use (
                            $studentEnrolmentId,
                            $module,
                            $marks,
                        ): array {
                            $key = $this->markKey($studentEnrolmentId, (int) $module->id, (int) $type['id']);
                            $saved = $marks->get($key)?->first();

                            return [
                                'assessmentTypeId' => $type['id'],
                                'assessmentTypeName' => $type['name'],
                                'markId' => $saved?->id,
                                'mark' => $saved?->mark,
                                'remark' => $saved?->remark,
                            ];
                        })->values()->all();

                        $modulePayload['assessments'] = $assessments;
                        $modulePayload['aggregation'] = $this->aggregationService->aggregateStudentModule(
                            $context['assessmentTypes'],
                            $assessments,
                        );
                    }

                    return $modulePayload;
                })->values()->all(),
            ];
        })->values()->all();

        return [
            'academicCalendarClassId' => $academicCalendarClassId,
            'studentEnrolmentId' => $studentEnrolmentId,
            'student' => $student,
            'modeOfStudyId' => (int) $classConfig->mode_of_study_id,
            'syllabi' => $syllabiPayload,
            'assessmentTypes' => $context['assessmentTypes'],
        ];
    }

    /**
     * @return list<array{studentEnrolmentId: int, studentId: int, name: string, studentNumber: string|null}>
     */
    private function studentsForClass(int $academicCalendarClassId): array
    {
        return AcademicCalendarStudentEnrolment::query()
            ->join('student_enrolments', 'student_enrolments.id', '=', 'academic_calendar_student_enrolments.student_enrolment_id')
            ->join('student_applications', 'student_applications.id', '=', 'student_enrolments.student_application_id')
            ->join('students', 'students.id', '=', 'student_applications.student_id')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->where('academic_calendar_student_enrolments.academic_calendar_class_id', $academicCalendarClassId)
            ->whereNull('academic_calendar_student_enrolments.deleted_at')
            ->select([
                'student_enrolments.id as student_enrolment_id',
                'student_applications.application_tracking_number',
                'students.student_number',
                'users.id as user_id',
                'users.first_name',
                'users.last_name',
            ])
            ->orderBy('users.first_name')
            ->orderBy('users.last_name')
            ->get()
            ->map(fn ($row): array => $this->mapStudentRow($row))
            ->values()
            ->all();
    }

    /**
     * @return list<array{studentEnrolmentId: int, studentId: int, name: string, studentNumber: string|null, academicCalendarClassId: int, className: string}>
     */
    private function studentsForClassConfig(int $classConfigId): array
    {
        return AcademicCalendarStudentEnrolment::query()
            ->join('academic_calendar_classes', 'academic_calendar_classes.id', '=', 'academic_calendar_student_enrolments.academic_calendar_class_id')
            ->join('student_enrolments', 'student_enrolments.id', '=', 'academic_calendar_student_enrolments.student_enrolment_id')
            ->join('student_applications', 'student_applications.id', '=', 'student_enrolments.student_application_id')
            ->join('students', 'students.id', '=', 'student_applications.student_id')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->where('academic_calendar_classes.class_config_id', $classConfigId)
            ->whereNull('academic_calendar_student_enrolments.deleted_at')
            ->whereNull('academic_calendar_classes.deleted_at')
            ->select([
                'student_enrolments.id as student_enrolment_id',
                'student_applications.application_tracking_number',
                'students.student_number',
                'users.id as user_id',
                'users.first_name',
                'users.last_name',
                'academic_calendar_classes.id as academic_calendar_class_id',
                'academic_calendar_classes.name as class_name',
            ])
            ->orderBy('academic_calendar_classes.name')
            ->orderBy('users.first_name')
            ->orderBy('users.last_name')
            ->get()
            ->map(function ($row): array {
                $student = $this->mapStudentRow($row);
                $student['academicCalendarClassId'] = (int) $row->academic_calendar_class_id;
                $student['className'] = (string) $row->class_name;

                return $student;
            })
            ->values()
            ->all();
    }

    /**
     * @return array{studentEnrolmentId: int, studentId: int, name: string, studentNumber: string|null}
     */
    private function mapStudentRow(object $row): array
    {
        return [
            'studentEnrolmentId' => (int) $row->student_enrolment_id,
            'studentId' => (int) $row->user_id,
            'name' => trim(sprintf('%s %s', (string) ($row->first_name ?? ''), (string) ($row->last_name ?? ''))),
            'studentNumber' => $row->student_number ?: $row->application_tracking_number,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $syllabiPayload
     * @return list<array{moduleId: int, moduleCode: string|null, moduleTitle: string|null, completeCount: int, studentCount: int}>
     */
    private function buildMarksheetSummary(array $syllabiPayload): array
    {
        $summary = [];

        foreach ($syllabiPayload as $syllabus) {
            foreach ($syllabus['modules'] ?? [] as $module) {
                $students = $module['students'] ?? [];
                $completeCount = collect($students)
                    ->filter(function (array $student): bool {
                        if (isset($student['moduleMark'])) {
                            return (bool) ($student['moduleMark']['isComplete'] ?? false);
                        }

                        return (bool) ($student['aggregation']['isComplete'] ?? false);
                    })
                    ->count();

                $summary[] = [
                    'moduleId' => (int) $module['id'],
                    'moduleCode' => $module['code'] ?? null,
                    'moduleTitle' => $module['title'] ?? null,
                    'completeCount' => $completeCount,
                    'studentCount' => count($students),
                ];
            }
        }

        return $summary;
    }

    private function markKey(int $studentEnrolmentId, int $moduleId, ?int $assessmentTypeId): string
    {
        if ($assessmentTypeId === null) {
            return sprintf('%d:%d:mark-only', $studentEnrolmentId, $moduleId);
        }

        return sprintf('%d:%d:%d', $studentEnrolmentId, $moduleId, $assessmentTypeId);
    }

    /**
     * @param  array<string, mixed>  $student
     * @param  Collection<string, Collection<int, CourseWorkMark>>  $marks
     * @param  list<array{id: int, name: string, description: string|null, weightPercent: int|null}>  $assessmentTypes
     * @return array<string, mixed>
     */
    private function buildStudentRowForModule(
        array $student,
        CourseSyllabusModule $module,
        Collection $marks,
        array $assessmentTypes,
        bool $includeClassFields,
    ): array {
        $row = [
            'studentEnrolmentId' => $student['studentEnrolmentId'],
            'studentId' => $student['studentId'],
            'name' => $student['name'],
            'studentNumber' => $student['studentNumber'],
        ];

        if ($module->capture_mark_only) {
            $saved = $marks->get($this->markKey(
                (int) $student['studentEnrolmentId'],
                (int) $module->id,
                null,
            ))?->first();
            $row['moduleMark'] = $this->formatModuleMark($saved);
            $row['assessments'] = [];
        } else {
            $assessments = collect($assessmentTypes)->map(function (array $type) use ($student, $module, $marks): array {
                $key = $this->markKey(
                    (int) $student['studentEnrolmentId'],
                    (int) $module->id,
                    (int) $type['id'],
                );
                $saved = $marks->get($key)?->first();

                return [
                    'assessmentTypeId' => $type['id'],
                    'assessmentTypeName' => $type['name'],
                    'markId' => $saved?->id,
                    'mark' => $saved?->mark,
                    'remark' => $saved?->remark,
                ];
            })->values()->all();

            $row['assessments'] = $assessments;
            $row['aggregation'] = $this->aggregationService->aggregateStudentModule(
                $assessmentTypes,
                $assessments,
            );
        }

        if ($includeClassFields) {
            $row['academicCalendarClassId'] = $student['academicCalendarClassId'] ?? null;
            $row['className'] = $student['className'] ?? null;
        }

        return $row;
    }

    /**
     * @return array{markId: int|null, mark: int|null, remark: string|null, isComplete: bool}
     */
    private function formatModuleMark(?CourseWorkMark $saved): array
    {
        return [
            'markId' => $saved?->id,
            'mark' => $saved?->mark,
            'remark' => $saved?->remark,
            'isComplete' => $saved?->mark !== null,
        ];
    }
}
