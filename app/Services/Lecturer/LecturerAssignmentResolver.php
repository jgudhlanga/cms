<?php

namespace App\Services\Lecturer;

use App\Enums\AcademicCalendars\ClassMetaDataTypeEnum;
use App\Helpers\Helper;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarClassMetaData;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\ClassMetaDataType;
use App\Models\Institution\Staff;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Users\User;
use App\Support\Institution\CourseSyllabusModulePeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LecturerAssignmentResolver
{
    /**
     * @return array{
     *     staff: Staff|null,
     *     classIds: list<int>,
     *     moduleIds: list<int>,
     *     assignmentKeys: list<string>,
     *     tutorClassIds: list<int>
     * }
     */
    public function resolveForUser(User $user): array
    {
        $staff = $user->staffProfile;

        if (! $staff instanceof Staff) {
            return [
                'staff' => null,
                'classIds' => [],
                'moduleIds' => [],
                'assignmentKeys' => [],
                'tutorClassIds' => [],
            ];
        }

        return $this->resolveForStaff($staff);
    }

    /**
     * @return array{
     *     staff: Staff,
     *     classIds: list<int>,
     *     moduleIds: list<int>,
     *     assignmentKeys: list<string>,
     *     tutorClassIds: list<int>
     * }
     */
    public function resolveForStaff(Staff $staff): array
    {
        $staffId = (int) $staff->id;
        $tutorClassIds = $this->tutorClassIds($staffId);
        $pivotRows = $this->pivotRows($staffId);

        $assignmentKeys = [];
        $classIds = $tutorClassIds;
        $moduleIds = [];

        foreach ($pivotRows as $row) {
            $moduleId = (int) $row->course_syllabus_module_id;
            $moduleIds[] = $moduleId;

            if ($row->academic_calendar_class_id !== null) {
                $classId = (int) $row->academic_calendar_class_id;
                $classIds[] = $classId;
                $assignmentKeys[] = $this->assignmentKey($classId, $moduleId);

                continue;
            }

            foreach ($this->classIdsUsingModule($moduleId) as $classId) {
                $classIds[] = $classId;
                $assignmentKeys[] = $this->assignmentKey($classId, $moduleId);
            }
        }

        foreach ($tutorClassIds as $classId) {
            foreach ($this->moduleIdsForClass($classId) as $moduleId) {
                $moduleIds[] = $moduleId;
                $assignmentKeys[] = $this->assignmentKey($classId, $moduleId);
            }
        }

        $classIds = array_values(array_unique($classIds));
        $moduleIds = array_values(array_unique($moduleIds));
        $assignmentKeys = array_values(array_unique($assignmentKeys));

        return [
            'staff' => $staff,
            'classIds' => $classIds,
            'moduleIds' => $moduleIds,
            'assignmentKeys' => $assignmentKeys,
            'tutorClassIds' => $tutorClassIds,
        ];
    }

    public function assignmentKey(int $classId, int $moduleId): string
    {
        return $classId.'-'.$moduleId;
    }

    public function isAssigned(array $resolved, int $classId, int $moduleId): bool
    {
        return in_array($this->assignmentKey($classId, $moduleId), $resolved['assignmentKeys'] ?? [], true);
    }

    /**
     * @return list<int>
     */
    private function tutorClassIds(int $staffId): array
    {
        $lecturerTypeId = ClassMetaDataType::query()
            ->where('name', ClassMetaDataTypeEnum::LECTURER->value)
            ->value('id');

        if ($lecturerTypeId === null) {
            return [];
        }

        return AcademicCalendarClassMetaData::query()
            ->where('staff_id', $staffId)
            ->where('class_metadata_type_id', $lecturerTypeId)
            ->pluck('academic_calendar_class_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, object{course_syllabus_module_id: int|string, academic_calendar_class_id: int|string|null}>
     */
    private function pivotRows(int $staffId): Collection
    {
        return DB::table('course_syllabus_module_lecturers')
            ->where('staff_id', $staffId)
            ->get(['course_syllabus_module_id', 'academic_calendar_class_id']);
    }

    /**
     * @return list<int>
     */
    private function classIdsUsingModule(int $moduleId): array
    {
        $module = CourseSyllabusModule::query()->find($moduleId);

        if ($module === null) {
            return [];
        }

        $syllabusId = (int) $module->course_syllabus_id;
        $academicCalendar = Helper::resolveAcademicCalendar();
        $calendarYear = (string) $academicCalendar->calendar_year;

        $classConfigs = ClassConfig::query()
            ->where('calendar_year', $calendarYear)
            ->get(['id', 'course_syllabus_ids', 'academic_year_option_id']);

        if ($classConfigs->isEmpty()) {
            return [];
        }

        $matchingConfigIds = [];

        foreach ($classConfigs as $classConfig) {
            $syllabusIds = array_values(array_map(
                'intval',
                array_filter($classConfig->course_syllabus_ids ?? []),
            ));

            if ($syllabusIds === [] || ! in_array($syllabusId, $syllabusIds, true)) {
                continue;
            }

            $slugPrefix = CourseSyllabusModulePeriod::slugPrefixForSyllabus($syllabusIds[0]);
            $matchesPeriod = CourseSyllabusModule::query()
                ->whereKey($moduleId)
                ->where(function ($query) use ($classConfig, $slugPrefix): void {
                    CourseSyllabusModulePeriod::scopeForPeriod(
                        $query,
                        (int) $classConfig->academic_year_option_id,
                        $slugPrefix,
                    );
                })
                ->exists();

            if ($matchesPeriod) {
                $matchingConfigIds[] = (int) $classConfig->id;
            }
        }

        if ($matchingConfigIds === []) {
            return [];
        }

        return AcademicCalendarClass::query()
            ->whereIn('class_config_id', $matchingConfigIds)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * @return list<int>
     */
    private function moduleIdsForClass(int $classId): array
    {
        $class = AcademicCalendarClass::query()
            ->with('classConfig')
            ->find($classId);

        if ($class === null || $class->classConfig === null) {
            return [];
        }

        $classConfig = $class->classConfig;
        $syllabusIds = array_values(array_map(
            'intval',
            array_filter($classConfig->course_syllabus_ids ?? []),
        ));

        if ($syllabusIds === []) {
            return [];
        }

        $slugPrefix = CourseSyllabusModulePeriod::slugPrefixForSyllabus($syllabusIds[0]);

        return CourseSyllabusModule::query()
            ->whereIn('course_syllabus_id', $syllabusIds)
            ->where(function ($query) use ($classConfig, $slugPrefix): void {
                CourseSyllabusModulePeriod::scopeForPeriod(
                    $query,
                    (int) $classConfig->academic_year_option_id,
                    $slugPrefix,
                );
            })
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }
}
