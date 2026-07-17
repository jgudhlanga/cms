<?php

namespace App\Services\Lecturer;

use App\Helpers\Helper;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Users\User;
use Illuminate\Support\Collection;

class LecturerTeachingListService
{
    public function __construct(
        private readonly LecturerAssignmentResolver $assignmentResolver,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function classesFor(User $user): array
    {
        $resolved = $this->assignmentResolver->resolveForUser($user);

        if ($resolved['classIds'] === []) {
            return [];
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
            ->get();

        return $classes
            ->filter(function (AcademicCalendarClass $class) use ($calendarYear): bool {
                return (string) ($class->classConfig?->calendar_year ?? '') === $calendarYear;
            })
            ->map(function (AcademicCalendarClass $class) use ($resolved): array {
                $config = $class->classConfig;
                $moduleCount = collect($resolved['assignmentKeys'])
                    ->filter(fn (string $key): bool => str_starts_with($key, $class->id.'-'))
                    ->count();

                return [
                    'id' => (int) $class->id,
                    'name' => (string) $class->name,
                    'description' => $class->description,
                    'departmentName' => (string) ($config?->institutionDepartment?->department?->name ?? ''),
                    'courseName' => (string) ($config?->departmentCourse?->course?->name ?? ''),
                    'levelName' => (string) ($config?->departmentLevel?->level?->name ?? ''),
                    'modeOfStudyName' => (string) ($config?->modeOfStudy?->name ?? ''),
                    'calendarYear' => (string) ($config?->calendar_year ?? ''),
                    'modulesCount' => $moduleCount,
                    'isTutor' => in_array((int) $class->id, $resolved['tutorClassIds'], true),
                ];
            })
            ->values()
            ->all();
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
}
