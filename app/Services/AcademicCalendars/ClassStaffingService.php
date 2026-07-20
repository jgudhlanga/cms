<?php

declare(strict_types=1);

namespace App\Services\AcademicCalendars;

use App\Enums\AcademicCalendars\ClassMetaDataTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarClassMetaData;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\ClassMetaDataType;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Staff;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Support\Institution\CourseSyllabusModulePeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClassStaffingService
{
    public function resolveSemesterClassConfig(
        ClassConfig $allocationConfig,
        ?int $academicYearOptionId,
    ): ?ClassConfig {
        if ($academicYearOptionId === null || $academicYearOptionId < 1) {
            return null;
        }

        return ClassConfig::query()
            ->where('calendar_year', $allocationConfig->calendar_year)
            ->where('institution_department_id', $allocationConfig->institution_department_id)
            ->where('department_course_id', $allocationConfig->department_course_id)
            ->where('department_level_id', $allocationConfig->department_level_id)
            ->where('mode_of_study_id', $allocationConfig->mode_of_study_id)
            ->where('academic_year_option_id', $academicYearOptionId)
            ->first();
    }

    /**
     * @return Collection<int, CourseSyllabusModule>
     */
    public function resolveSemesterModules(ClassConfig $semesterConfig): Collection
    {
        $syllabusIds = array_values(array_map(
            'intval',
            array_filter($semesterConfig->course_syllabus_ids ?? []),
        ));

        if ($syllabusIds === [] || $semesterConfig->academic_year_option_id === null) {
            return collect();
        }

        $slugPrefix = CourseSyllabusModulePeriod::slugPrefixForSyllabus($syllabusIds[0]);

        return CourseSyllabusModule::query()
            ->whereIn('course_syllabus_id', $syllabusIds)
            ->where(function ($query) use ($semesterConfig, $slugPrefix): void {
                CourseSyllabusModulePeriod::scopeForPeriod(
                    $query,
                    (int) $semesterConfig->academic_year_option_id,
                    $slugPrefix,
                );
            })
            ->orderBy('code')
            ->get();
    }

    /**
     * @param  list<int>  $classIds
     * @return array<int, array{id: int, name: string}|null>
     */
    public function tutorsByClassId(array $classIds): array
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

        return AcademicCalendarClassMetaData::query()
            ->whereIn('academic_calendar_class_id', $classIds)
            ->where('class_metadata_type_id', $lecturerTypeId)
            ->whereNull('deleted_at')
            ->with('staff.user')
            ->get()
            ->mapWithKeys(function (AcademicCalendarClassMetaData $meta): array {
                $user = $meta->staff?->user;
                $name = trim((string) ($user?->name ?? ''));

                if ($name === '' && $user !== null) {
                    $name = trim(((string) ($user->first_name ?? '')).' '.((string) ($user->last_name ?? '')));
                }

                return [
                    (int) $meta->academic_calendar_class_id => $name !== ''
                        ? ['id' => (int) $meta->staff_id, 'name' => $name]
                        : null,
                ];
            })
            ->all();
    }

    /**
     * @param  list<int>  $classIds
     * @param  Collection<int, CourseSyllabusModule>  $modules
     * @return array<int, array<int, list<int>>>
     */
    public function classModuleStaffIdsByClassId(array $classIds, Collection $modules): array
    {
        if ($classIds === [] || $modules->isEmpty()) {
            return [];
        }

        $moduleIds = $modules->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $rows = DB::table('course_syllabus_module_lecturers')
            ->whereIn('course_syllabus_module_id', $moduleIds)
            ->whereIn('academic_calendar_class_id', $classIds)
            ->whereNotNull('academic_calendar_class_id')
            ->get(['course_syllabus_module_id', 'academic_calendar_class_id', 'staff_id']);

        $grouped = [];

        foreach ($rows as $row) {
            $classId = (int) $row->academic_calendar_class_id;
            $moduleId = (int) $row->course_syllabus_module_id;
            $grouped[$classId][$moduleId][] = (int) $row->staff_id;
        }

        return $grouped;
    }

    /**
     * @param  Collection<int, CourseSyllabusModule>  $modules
     * @return array<int, list<int>>
     */
    public function templateStaffIdsByModuleId(Collection $modules): array
    {
        if ($modules->isEmpty()) {
            return [];
        }

        $moduleIds = $modules->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $rows = DB::table('course_syllabus_module_lecturers')
            ->whereIn('course_syllabus_module_id', $moduleIds)
            ->whereNull('academic_calendar_class_id')
            ->get(['course_syllabus_module_id', 'staff_id']);

        $grouped = [];

        foreach ($rows as $row) {
            $grouped[(int) $row->course_syllabus_module_id][] = (int) $row->staff_id;
        }

        return $grouped;
    }

    /**
     * @param  list<array<string, mixed>>  $classPreviews
     * @return array<string, mixed>
     */
    public function buildStaffingSummary(
        array $classPreviews,
        Collection $modules,
        array $tutorsByClassId,
        array $classModuleStaffIdsByClassId,
    ): array {
        $savedClasses = array_values(array_filter(
            $classPreviews,
            fn (array $preview): bool => ($preview['academicCalendarClassId'] ?? null) !== null,
        ));

        $classCount = count($savedClasses);
        $tutorsAssigned = 0;
        $modulesTotal = $modules->count() * max(1, $classCount);
        $moduleSlotsStaffed = 0;

        foreach ($savedClasses as $preview) {
            $classId = (int) $preview['academicCalendarClassId'];

            if (($tutorsByClassId[$classId] ?? null) !== null) {
                $tutorsAssigned++;
            }

            foreach ($modules as $module) {
                $staffIds = $classModuleStaffIdsByClassId[$classId][$module->id] ?? [];

                if ($staffIds !== []) {
                    $moduleSlotsStaffed++;
                }
            }
        }

        return [
            'tutorsAssigned' => $tutorsAssigned,
            'classCount' => $classCount,
            'modulesTotal' => $modulesTotal,
            'moduleSlotsStaffed' => $moduleSlotsStaffed,
            'semesterModuleCount' => $modules->count(),
        ];
    }

    /**
     * @param  Collection<int, CourseSyllabusModule>  $modules
     * @return list<array<string, mixed>>
     */
    public function buildSemesterModulesPayload(
        AcademicCalendarClass $academicCalendarClass,
        Collection $modules,
        array $classModuleStaffIdsByClassId,
        array $templateStaffIdsByModuleId,
    ): array {
        $classId = (int) $academicCalendarClass->id;

        return $modules->map(function (CourseSyllabusModule $module) use (
            $classId,
            $classModuleStaffIdsByClassId,
            $templateStaffIdsByModuleId,
        ): array {
            $staffIds = $classModuleStaffIdsByClassId[$classId][$module->id] ?? [];
            $syllabusDefaultStaffIds = $templateStaffIdsByModuleId[$module->id] ?? [];

            return [
                'moduleId' => (int) $module->id,
                'code' => (string) $module->code,
                'title' => (string) $module->title,
                'captureMarkOnly' => (bool) $module->capture_mark_only,
                'staffIds' => array_values(array_unique($staffIds)),
                'syllabusDefaultStaffIds' => array_values(array_unique($syllabusDefaultStaffIds)),
            ];
        })->values()->all();
    }

    public function formatTutorSummary(?array $tutor): ?array
    {
        if ($tutor === null) {
            return null;
        }

        return [
            'id' => (int) $tutor['id'],
            'name' => (string) $tutor['name'],
        ];
    }

    /**
     * @param  Collection<int, CourseSyllabusModule>  $modules
     */
    public function moduleStaffingForClass(
        int $classId,
        Collection $modules,
        array $classModuleStaffIdsByClassId,
    ): array {
        $total = $modules->count();
        $staffed = 0;

        foreach ($modules as $module) {
            if (($classModuleStaffIdsByClassId[$classId][$module->id] ?? []) !== []) {
                $staffed++;
            }
        }

        return [
            'staffed' => $staffed,
            'total' => $total,
        ];
    }

    /**
     * @param  list<int>  $staffIds
     */
    public function syncClassModuleLecturers(
        AcademicCalendarClass $academicCalendarClass,
        CourseSyllabusModule $module,
        array $staffIds,
        int $tenantId,
    ): void {
        DB::table('course_syllabus_module_lecturers')
            ->where('course_syllabus_module_id', $module->id)
            ->where('academic_calendar_class_id', $academicCalendarClass->id)
            ->delete();

        $now = now();
        $uniqueStaffIds = array_values(array_unique(array_map('intval', $staffIds)));

        foreach ($uniqueStaffIds as $staffId) {
            DB::table('course_syllabus_module_lecturers')->insert([
                'tenant_id' => $tenantId,
                'course_syllabus_module_id' => $module->id,
                'staff_id' => $staffId,
                'academic_calendar_class_id' => $academicCalendarClass->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * @param  Collection<int, CourseSyllabusModule>  $modules
     */
    public function copyTemplateLecturersToClass(
        AcademicCalendarClass $academicCalendarClass,
        Collection $modules,
        int $tenantId,
    ): void {
        $templateStaffIdsByModuleId = $this->templateStaffIdsByModuleId($modules);

        foreach ($modules as $module) {
            $staffIds = $templateStaffIdsByModuleId[$module->id] ?? [];

            if ($staffIds === []) {
                continue;
            }

            $this->syncClassModuleLecturers($academicCalendarClass, $module, $staffIds, $tenantId);
        }
    }

    public function assignTutor(
        AcademicCalendarClass $academicCalendarClass,
        ?int $staffId,
        int $tenantId,
    ): void {
        $lecturerTypeId = ClassMetaDataType::query()
            ->where('name', ClassMetaDataTypeEnum::LECTURER->value)
            ->value('id');

        if ($lecturerTypeId === null) {
            return;
        }

        $existing = AcademicCalendarClassMetaData::query()
            ->where('academic_calendar_class_id', $academicCalendarClass->id)
            ->where('class_metadata_type_id', $lecturerTypeId)
            ->first();

        if ($staffId === null) {
            $existing?->delete();

            return;
        }

        if ($existing instanceof AcademicCalendarClassMetaData) {
            $existing->update(['staff_id' => $staffId]);

            return;
        }

        AcademicCalendarClassMetaData::query()->create([
            'tenant_id' => $tenantId,
            'academic_calendar_class_id' => $academicCalendarClass->id,
            'staff_id' => $staffId,
            'class_metadata_type_id' => $lecturerTypeId,
        ]);
    }

    /**
     * @param  list<int>  $staffIds
     */
    public function assertAcademicStaffInDepartment(
        InstitutionDepartment $institutionDepartment,
        array $staffIds,
    ): bool {
        if ($staffIds === []) {
            return true;
        }

        $roleSlugs = [
            'lecturer',
            'senior-lecturer',
            'lecturer-in-charge',
            'head-of-department',
        ];

        $validCount = Staff::query()
            ->whereIn('id', $staffIds)
            ->whereNull('deleted_at')
            ->whereHas(
                'institutionDepartments',
                fn ($query) => $query->where('institution_departments.id', $institutionDepartment->id),
            )
            ->whereHas('user.roles', fn ($query) => $query->whereIn('slug', $roleSlugs))
            ->count();

        return $validCount === count(array_unique($staffIds));
    }

    public function moduleBelongsToSemesterConfig(
        CourseSyllabusModule $module,
        ClassConfig $semesterConfig,
    ): bool {
        $syllabusIds = array_map('intval', $semesterConfig->course_syllabus_ids ?? []);

        if (! in_array((int) $module->course_syllabus_id, $syllabusIds, true)) {
            return false;
        }

        if ($semesterConfig->academic_year_option_id === null) {
            return false;
        }

        return CourseSyllabusModulePeriod::matchesPeriod(
            $module,
            (int) $semesterConfig->academic_year_option_id,
        );
    }

    /**
     * @return list<int>
     */
    public function syllabusIdsForSemesterConfig(ClassConfig $semesterConfig): array
    {
        return array_values(array_map(
            'intval',
            array_filter($semesterConfig->course_syllabus_ids ?? []),
        ));
    }

    /**
     * @param  list<int>  $classIds
     * @return array<int, array{
     *     academicCalendarClassId: int,
     *     name: string,
     *     studentCount: int,
     *     genderCounts: array{male: int, female: int, unknown: int},
     *     students: list<mixed>
     * }>
     */
    public function classPreviewsByClassId(array $classIds): array
    {
        if ($classIds === []) {
            return [];
        }

        return AcademicCalendarClass::query()
            ->leftJoin('academic_calendar_student_enrolments', function ($join): void {
                $join->on('academic_calendar_student_enrolments.academic_calendar_class_id', '=', 'academic_calendar_classes.id')
                    ->whereNull('academic_calendar_student_enrolments.deleted_at');
            })
            ->leftJoin('student_enrolments', 'student_enrolments.id', '=', 'academic_calendar_student_enrolments.student_enrolment_id')
            ->leftJoin('students', 'students.id', '=', 'student_enrolments.student_id')
            ->leftJoin('genders', 'genders.id', '=', 'students.gender_id')
            ->whereIn('academic_calendar_classes.id', $classIds)
            ->whereNull('academic_calendar_classes.deleted_at')
            ->groupBy('academic_calendar_classes.id', 'academic_calendar_classes.name')
            ->select([
                'academic_calendar_classes.id',
                'academic_calendar_classes.name',
                DB::raw('COUNT(academic_calendar_student_enrolments.id) as student_count'),
                DB::raw("SUM(CASE WHEN LOWER(genders.title) LIKE 'male%' THEN 1 ELSE 0 END) as male_count"),
                DB::raw("SUM(CASE WHEN LOWER(genders.title) LIKE 'female%' THEN 1 ELSE 0 END) as female_count"),
            ])
            ->orderBy('academic_calendar_classes.name')
            ->get()
            ->mapWithKeys(function (mixed $class): array {
                $maleCount = (int) ($class->male_count ?? 0);
                $femaleCount = (int) ($class->female_count ?? 0);
                $studentCount = (int) ($class->student_count ?? 0);
                $classId = (int) $class->id;

                return [
                    $classId => [
                        'academicCalendarClassId' => $classId,
                        'name' => (string) $class->name,
                        'studentCount' => $studentCount,
                        'genderCounts' => [
                            'male' => $maleCount,
                            'female' => $femaleCount,
                            'unknown' => max(0, $studentCount - ($maleCount + $femaleCount)),
                        ],
                        'students' => [],
                    ],
                ];
            })
            ->all();
    }
}
