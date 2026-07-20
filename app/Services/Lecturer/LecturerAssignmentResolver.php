<?php

namespace App\Services\Lecturer;

use App\Enums\AcademicCalendars\ClassMetaDataTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendarClassMetaData;
use App\Models\AcademicCalendars\ClassMetaDataType;
use App\Models\Institution\Staff;
use App\Models\Users\User;
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
            $classId = (int) $row->academic_calendar_class_id;
            $moduleIds[] = $moduleId;
            $classIds[] = $classId;
            $assignmentKeys[] = $this->assignmentKey($classId, $moduleId);
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
     * @return Collection<int, object{course_syllabus_module_id: int|string, academic_calendar_class_id: int|string}>
     */
    private function pivotRows(int $staffId): Collection
    {
        return DB::table('course_syllabus_module_lecturers')
            ->where('staff_id', $staffId)
            ->whereNotNull('academic_calendar_class_id')
            ->get(['course_syllabus_module_id', 'academic_calendar_class_id']);
    }
}
