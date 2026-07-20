<?php

namespace App\Services\Lecturer;

use App\Helpers\Helper;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Users\User;
use App\Services\AcademicCalendars\CourseWorkAssessmentLockService;
use App\Services\AcademicCalendars\ClassStaffingService;

class LecturerTeachingListService
{
    public function __construct(
        private readonly LecturerAssignmentResolver $assignmentResolver,
        private readonly ClassStaffingService $classStaffingService,
        private readonly LecturerTeachingClassesIndexService $classesIndexService,
        private readonly CourseWorkAssessmentLockService $courseWorkAssessmentLockService,
    ) {}

    /**
     * @return array{
     *     classes: list<array<string, mixed>>,
     *     summary: array<string, int>
     * }
     */
    public function classesIndexFor(User $user): array
    {
        return $this->classesIndexService->build($user);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function classesFor(User $user): array
    {
        return $this->classesIndexFor($user)['classes'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function modulesFor(User $user): array
    {
        $resolved = $this->assignmentResolver->resolveForUser($user);

        if ($resolved['assignmentKeys'] === []) {
            return [];
        }

        $moduleIds = $resolved['moduleIds'];
        $modules = CourseSyllabusModule::query()
            ->whereIn('id', $moduleIds)
            ->with(['courseSyllabus.institutionDepartment.department'])
            ->orderBy('code')
            ->get()
            ->keyBy('id');

        $classes = AcademicCalendarClass::query()
            ->whereIn('id', $resolved['classIds'])
            ->get(['id', 'name'])
            ->keyBy('id');

        $grouped = [];

        foreach ($resolved['assignmentKeys'] as $key) {
            [$classId, $moduleId] = array_map('intval', explode('-', $key, 2));
            $module = $modules->get($moduleId);

            if ($module === null) {
                continue;
            }

            if (! isset($grouped[$moduleId])) {
                $grouped[$moduleId] = [
                    'id' => $moduleId,
                    'title' => (string) $module->title,
                    'code' => (string) ($module->code ?? ''),
                    'departmentName' => (string) ($module->courseSyllabus?->institutionDepartment?->department?->name ?? ''),
                    'classes' => [],
                ];
            }

            $class = $classes->get($classId);

            if ($class !== null) {
                $grouped[$moduleId]['classes'][] = [
                    'id' => (int) $class->id,
                    'name' => (string) $class->name,
                ];
            }
        }

        return collect($grouped)
            ->map(function (array $row): array {
                $uniqueClasses = collect($row['classes'])->unique('id')->values()->all();
                $row['classes'] = $uniqueClasses;
                $row['classesCount'] = count($uniqueClasses);

                return $row;
            })
            ->sortBy('code')
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function classDetailFor(User $user, AcademicCalendarClass $class): ?array
    {
        $resolved = $this->assignmentResolver->resolveForUser($user);
        $classId = (int) $class->id;

        if (! in_array($classId, $resolved['classIds'], true)) {
            return null;
        }

        $class->loadMissing([
            'classConfig.institutionDepartment.department',
            'classConfig.departmentCourse.course',
            'classConfig.departmentLevel.level',
            'classConfig.modeOfStudy',
        ]);

        $config = $class->classConfig;
        $assignedModuleIds = [];

        foreach ($resolved['assignmentKeys'] as $key) {
            [$assignedClassId, $moduleId] = array_map('intval', explode('-', $key, 2));

            if ($assignedClassId === $classId) {
                $assignedModuleIds[] = $moduleId;
            }
        }

        $assignedModuleIds = array_values(array_unique($assignedModuleIds));
        $isTutor = in_array($classId, $resolved['tutorClassIds'], true);

        if ($isTutor && $config instanceof ClassConfig) {
            $moduleRecords = $this->classStaffingService
                ->resolveSemesterModules($config)
                ->values();
        } else {
            $moduleRecords = CourseSyllabusModule::query()
                ->whereIn('id', $assignedModuleIds)
                ->orderBy('code')
                ->get(['id', 'title', 'code', 'capture_mark_only']);
        }

        $moduleLocksById = $this->courseWorkAssessmentLockService->locksForClassAndModules($class, $moduleRecords);
        $modules = $moduleRecords
            ->map(function (CourseSyllabusModule $module) use ($assignedModuleIds, $isTutor, $moduleLocksById): array {
                $moduleId = (int) $module->id;
                $lock = $moduleLocksById[$moduleId] ?? [
                    'moduleId' => $moduleId,
                    'hasEditableCourseWork' => true,
                    'allAssessmentTypesLocked' => false,
                    'lockedAssessmentTypeIds' => [],
                    'lockedAssessmentTypeNames' => [],
                    'readOnlyMessage' => null,
                ];

                return [
                    'id' => $moduleId,
                    'title' => (string) $module->title,
                    'code' => (string) ($module->code ?? ''),
                    'canManage' => $isTutor
                        ? in_array($moduleId, $assignedModuleIds, true)
                        : true,
                    'captureMarkOnly' => (bool) $module->capture_mark_only,
                    'courseWorkLock' => $lock,
                ];
            })
            ->values()
            ->all();

        $studentCount = $class->studentEnrolments()
            ->whereNull('deleted_at')
            ->count();

        return [
            'id' => $classId,
            'name' => (string) $class->name,
            'description' => $class->description,
            'departmentName' => (string) ($config?->institutionDepartment?->department?->name ?? ''),
            'courseName' => (string) ($config?->departmentCourse?->course?->name ?? ''),
            'levelName' => (string) ($config?->departmentLevel?->level?->name ?? ''),
            'modeOfStudyName' => (string) ($config?->modeOfStudy?->name ?? ''),
            'calendarYear' => (string) ($config?->calendar_year ?? ''),
            'classConfigId' => (int) ($config?->id ?? 0),
            'institutionDepartmentId' => (int) ($config?->institution_department_id ?? 0),
            'isTutor' => $isTutor,
            'studentCount' => $studentCount,
            'students' => $this->studentsPayloadForAcademicCalendarClass($class),
            'modules' => $modules,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function moduleDetailFor(User $user, CourseSyllabusModule $module): ?array
    {
        $resolved = $this->assignmentResolver->resolveForUser($user);
        $moduleId = (int) $module->id;

        if (! in_array($moduleId, $resolved['moduleIds'], true)) {
            return null;
        }

        $module->loadMissing(['courseSyllabus.institutionDepartment.department']);

        $classIds = [];

        foreach ($resolved['assignmentKeys'] as $key) {
            [$classId, $assignedModuleId] = array_map('intval', explode('-', $key, 2));

            if ($assignedModuleId === $moduleId) {
                $classIds[] = $classId;
            }
        }

        $classIds = array_values(array_unique($classIds));
        $classes = AcademicCalendarClass::query()
            ->whereIn('id', $classIds)
            ->with(['classConfig'])
            ->orderBy('name')
            ->get();

        return [
            'id' => $moduleId,
            'title' => (string) $module->title,
            'code' => (string) ($module->code ?? ''),
            'departmentName' => (string) ($module->courseSyllabus?->institutionDepartment?->department?->name ?? ''),
            'classes' => $classes->map(function (AcademicCalendarClass $class): array {
                $config = $class->classConfig;

                return [
                    'id' => (int) $class->id,
                    'name' => (string) $class->name,
                    'classConfigId' => (int) ($config?->id ?? 0),
                    'institutionDepartmentId' => (int) ($config?->institution_department_id ?? 0),
                    'calendarYear' => (string) ($config?->calendar_year ?? ''),
                ];
            })->values()->all(),
        ];
    }

    /**
     * @return list<array{studentEnrolmentId: int, studentId: int, applicationTrackingNumber: mixed, studentNumber: mixed, gender: mixed, name: string}>
     */
    private function studentsPayloadForAcademicCalendarClass(AcademicCalendarClass $academicCalendarClass): array
    {
        return AcademicCalendarStudentEnrolment::query()
            ->join('student_enrolments', 'student_enrolments.id', '=', 'academic_calendar_student_enrolments.student_enrolment_id')
            ->join('student_applications', 'student_applications.id', '=', 'student_enrolments.student_application_id')
            ->join('students', 'students.id', '=', 'student_applications.student_id')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->leftJoin('genders', 'genders.id', '=', 'students.gender_id')
            ->where('academic_calendar_student_enrolments.academic_calendar_class_id', $academicCalendarClass->id)
            ->whereNull('academic_calendar_student_enrolments.deleted_at')
            ->select([
                'student_enrolments.id as student_enrolment_id',
                'student_applications.application_tracking_number',
                'students.student_number',
                'users.id as user_id',
                'genders.title as gender_title',
                'users.first_name',
                'users.last_name',
            ])
            ->orderBy('users.first_name')
            ->orderBy('users.last_name')
            ->get()
            ->map(function (AcademicCalendarStudentEnrolment $row): array {
                return [
                    'studentEnrolmentId' => (int) $row->student_enrolment_id,
                    'studentId' => (int) $row->user_id,
                    'applicationTrackingNumber' => $row->application_tracking_number,
                    'studentNumber' => $row->student_number ?: $row->application_tracking_number,
                    'gender' => $row->gender_title,
                    'name' => trim(sprintf('%s %s', (string) ($row->first_name ?? ''), (string) ($row->last_name ?? ''))),
                ];
            })
            ->values()
            ->all();
    }
}
