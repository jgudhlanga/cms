<?php

namespace App\Services\Assessments;

use App\Enums\AcademicCalendars\ClassMetaDataTypeEnum;
use App\Enums\Institution\ModeOfStudyEnum;
use App\Helpers\Helper;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarClassMetaData;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\ClassMetaDataType;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\AssessmentType;
use App\Models\Institution\Staff;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Students\StudentEnrolment;
use App\Support\Institution\CourseSyllabusModulePeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MissingMarksQueryService
{
    /** @var array<int, list<CourseSyllabusModule>> */
    private array $modulesByClassConfigId = [];

    /**
     * @param  list<int>|null  $institutionDepartmentIds
     * @return list<array<string, mixed>>
     */
    public function forCalendar(AssessmentCalendar $calendar, ?array $institutionDepartmentIds = null): array
    {
        $assessmentType = $calendar->assessmentType ?? $calendar->assessmentType()->first();

        if (! $assessmentType instanceof AssessmentType) {
            return [];
        }

        $modeIds = array_values(array_filter(
            array_map('intval', $assessmentType->modes_of_study ?? []),
            static fn (int $id): bool => $id > 0,
        ));

        if ($modeIds === []) {
            return [];
        }

        $enrolments = $this->enrolmentsQuery($calendar, $modeIds, $institutionDepartmentIds)
            ->with([
                'student.user',
                'institutionDepartment.department',
                'academicCalendarStudentEnrolment.academicCalendarClass.classConfig',
            ])
            ->get();

        if ($enrolments->isEmpty()) {
            return [];
        }

        return $this->missingRowsForEnrolments($enrolments, (int) $assessmentType->id);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forCalendarForCurrentUser(AssessmentCalendar $calendar): array
    {
        $departmentIds = Helper::isDepartmentUser() ? Helper::resolveUserDepartments() : null;

        return $this->forCalendar($calendar, $departmentIds === [] ? [-1] : $departmentIds);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forStudentEnrolment(StudentEnrolment $enrolment, AssessmentCalendar $calendar): array
    {
        $assessmentType = $calendar->assessmentType ?? $calendar->assessmentType()->first();

        if (! $assessmentType instanceof AssessmentType) {
            return [];
        }

        $modeIds = array_values(array_filter(
            array_map('intval', $assessmentType->modes_of_study ?? []),
            static fn (int $id): bool => $id > 0,
        ));

        if ($modeIds === [] || ! in_array((int) $enrolment->mode_of_study_id, $modeIds, true)) {
            return [];
        }

        $enrolment->loadMissing([
            'student.user',
            'institutionDepartment.department',
            'academicCalendarStudentEnrolment.academicCalendarClass.classConfig',
        ]);

        return $this->missingRowsForEnrolments(collect([$enrolment]), (int) $assessmentType->id);
    }

    public function hasMissingMarks(AssessmentCalendar $calendar, ?array $institutionDepartmentIds = null): bool
    {
        return $this->forCalendar($calendar, $institutionDepartmentIds) !== [];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public function groupedByClassModule(array $rows): array
    {
        return collect($rows)
            ->groupBy(fn (array $row): string => $row['academicCalendarClassId'].'-'.$row['moduleId'])
            ->map(function (Collection $group): array {
                $first = $group->first();

                return [
                    'academicCalendarClassId' => (int) $first['academicCalendarClassId'],
                    'className' => (string) $first['className'],
                    'moduleId' => (int) $first['moduleId'],
                    'moduleName' => (string) $first['moduleName'],
                    'moduleCode' => (string) $first['moduleCode'],
                    'departmentId' => (int) $first['departmentId'],
                    'departmentName' => (string) $first['departmentName'],
                    'institutionDepartmentId' => (int) $first['institutionDepartmentId'],
                    'lecturerStaffIds' => array_values(array_unique(array_merge(...$group->pluck('lecturerStaffIds')->all()))),
                    'lecturerUserIds' => array_values(array_unique(array_merge(...$group->pluck('lecturerUserIds')->all()))),
                    'lecturerNames' => array_values(array_unique(array_merge(...$group->pluck('lecturerNames')->all()))),
                    'incompleteCount' => $group->count(),
                ];
            })
            ->sortByDesc('incompleteCount')
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $modeIds
     * @param  list<int>|null  $institutionDepartmentIds
     */
    private function enrolmentsQuery(
        AssessmentCalendar $calendar,
        array $modeIds,
        ?array $institutionDepartmentIds,
    ): Builder {
        $query = StudentEnrolment::query()
            ->where('academic_calendar_id', (int) $calendar->academic_calendar_id)
            ->whereIn('mode_of_study_id', $modeIds)
            ->whereHas(
                'modeOfStudy',
                fn (Builder $modeQuery): Builder => $modeQuery->where('name', '!=', ModeOfStudyEnum::OJET->value),
            )
            ->whereHas('academicCalendarStudentEnrolment');

        if ($institutionDepartmentIds !== null) {
            $query->whereIn('institution_department_id', $institutionDepartmentIds);
        }

        return $query;
    }

    /**
     * @param  Collection<int, StudentEnrolment>  $enrolments
     * @return list<array<string, mixed>>
     */
    private function missingRowsForEnrolments(Collection $enrolments, int $assessmentTypeId): array
    {
        $classIds = $enrolments
            ->map(fn (StudentEnrolment $enrolment): ?int => $enrolment->academicCalendarStudentEnrolment?->academic_calendar_class_id)
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        $lecturersByAssignment = $this->lecturersByAssignmentKey($classIds);
        $tutorsByClassId = $this->tutorsByClassId($classIds);

        $enrolmentIds = $enrolments->pluck('id')->all();
        $marks = CourseWorkMark::query()
            ->whereIn('student_enrolment_id', $enrolmentIds)
            ->where('assessment_type_id', $assessmentTypeId)
            ->get()
            ->groupBy(fn (CourseWorkMark $mark): string => $mark->student_enrolment_id.'-'.$mark->course_syllabus_module_id);

        $rows = [];

        foreach ($enrolments as $enrolment) {
            $class = $enrolment->academicCalendarStudentEnrolment?->academicCalendarClass;

            if (! $class instanceof AcademicCalendarClass) {
                continue;
            }

            $classConfig = $class->classConfig ?? ClassConfig::query()->find($class->class_config_id);

            if (! $classConfig instanceof ClassConfig) {
                continue;
            }

            $modules = $this->modulesForClassConfig($classConfig);
            $department = $enrolment->institutionDepartment?->department;
            $studentUser = $enrolment->student?->user;
            $studentName = $studentUser?->full_name ?? __('dashboard.lecturer_unknown_student');

            foreach ($modules as $module) {
                if ($module->capture_mark_only) {
                    continue;
                }

                $markKey = $enrolment->id.'-'.$module->id;
                $markGroup = $marks->get($markKey, collect());
                $saved = $markGroup->first();

                if ($saved instanceof CourseWorkMark && $saved->mark !== null) {
                    continue;
                }

                $assignmentKey = $class->id.'-'.$module->id;
                $lecturers = $lecturersByAssignment[$assignmentKey] ?? ($tutorsByClassId[(int) $class->id] ?? []);

                $rows[] = [
                    'studentEnrolmentId' => (int) $enrolment->id,
                    'studentId' => (int) $enrolment->student_id,
                    'studentUserId' => $studentUser?->id,
                    'studentName' => $studentName,
                    'moduleId' => (int) $module->id,
                    'moduleName' => (string) $module->title,
                    'moduleCode' => (string) ($module->code ?? ''),
                    'academicCalendarClassId' => (int) $class->id,
                    'className' => (string) $class->name,
                    'departmentId' => (int) ($department?->id ?? 0),
                    'departmentName' => (string) ($department?->name ?? __('dashboard.academic_unknown_department')),
                    'institutionDepartmentId' => (int) $enrolment->institution_department_id,
                    'lecturerStaffIds' => array_values(array_map(fn (array $lecturer): int => $lecturer['staffId'], $lecturers)),
                    'lecturerUserIds' => array_values(array_filter(array_map(
                        fn (array $lecturer): ?int => $lecturer['userId'],
                        $lecturers,
                    ))),
                    'lecturerNames' => array_values(array_map(fn (array $lecturer): string => $lecturer['name'], $lecturers)),
                ];
            }
        }

        return $rows;
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

        if ($syllabusIds === [] || $classConfig->semester_id === null) {
            return $this->modulesByClassConfigId[$classConfigId] = [];
        }

        $slugPrefix = CourseSyllabusModulePeriod::slugPrefixForSyllabus($syllabusIds[0]);

        return $this->modulesByClassConfigId[$classConfigId] = CourseSyllabusModule::query()
            ->whereIn('course_syllabus_id', $syllabusIds)
            ->where(function ($query) use ($classConfig, $slugPrefix): void {
                CourseSyllabusModulePeriod::scopeForPeriod(
                    $query,
                    (int) $classConfig->semester_id,
                    $slugPrefix,
                );
            })
            ->orderBy('code')
            ->get()
            ->all();
    }

    /**
     * @param  list<int>  $classIds
     * @return array<string, list<array{staffId: int, userId: int|null, name: string}>>
     */
    private function lecturersByAssignmentKey(array $classIds): array
    {
        if ($classIds === []) {
            return [];
        }

        $rows = DB::table('course_syllabus_module_lecturers')
            ->whereIn('academic_calendar_class_id', $classIds)
            ->get();

        $staffIds = $rows->pluck('staff_id')->map(fn ($id): int => (int) $id)->unique()->values()->all();
        $staffById = $this->staffById($staffIds);

        $grouped = [];

        foreach ($rows as $row) {
            $staff = $staffById[(int) $row->staff_id] ?? null;
            $key = ((int) $row->academic_calendar_class_id).'-'.((int) $row->course_syllabus_module_id);
            $grouped[$key][] = $this->lecturerPayload((int) $row->staff_id, $staff);
        }

        return $grouped;
    }

    /**
     * @param  list<int>  $classIds
     * @return array<int, list<array{staffId: int, userId: int|null, name: string}>>
     */
    private function tutorsByClassId(array $classIds): array
    {
        if ($classIds === []) {
            return [];
        }

        $lecturerTypeId = ClassMetaDataType::query()
            ->where('name', ClassMetaDataTypeEnum::LECTURER->value)
            ->value('id');

        if ($lecturerTypeId === null) {
            return [];
        }

        $assignments = AcademicCalendarClassMetaData::query()
            ->whereIn('academic_calendar_class_id', $classIds)
            ->where('class_metadata_type_id', $lecturerTypeId)
            ->whereNotNull('staff_id')
            ->with('staff.user')
            ->get();

        $grouped = [];

        foreach ($assignments as $assignment) {
            $staff = $assignment->staff;
            $grouped[(int) $assignment->academic_calendar_class_id][] = $this->lecturerPayload(
                (int) $assignment->staff_id,
                $staff,
            );
        }

        return $grouped;
    }

    /**
     * @param  list<int>  $staffIds
     * @return array<int, Staff>
     */
    private function staffById(array $staffIds): array
    {
        if ($staffIds === []) {
            return [];
        }

        return Staff::query()
            ->whereIn('id', $staffIds)
            ->with('user')
            ->get()
            ->keyBy(fn (Staff $staff): int => (int) $staff->id)
            ->all();
    }

    /**
     * @return array{staffId: int, userId: int|null, name: string}
     */
    private function lecturerPayload(int $staffId, ?Staff $staff): array
    {
        $user = $staff?->user;

        return [
            'staffId' => $staffId,
            'userId' => $user?->id,
            'name' => $user?->full_name ?: __('dashboard.academic_unknown_lecturer'),
        ];
    }
}
