<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Enums\Shared\ClassListTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentProgram;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DepartmentAcademicCalendarController extends Controller
{
    public function __construct() {}

    public function departmentAcademicCalendar(InstitutionDepartment $institutionDepartment): JsonResponse
    {
        $department = $this->loadDepartmentWithCourses($institutionDepartment);
        $context = $this->resolveContext();

        $lookups = $context === null
            ? $this->emptyLookups()
            : $this->buildLookups($department, $context);

        return response()->json($this->formatDepartment($department, $lookups));
    }

    private function loadDepartmentWithCourses(InstitutionDepartment $institutionDepartment): InstitutionDepartment
    {
        return InstitutionDepartment::with([
            'departmentCourses.course',
            'departmentCourses.departmentCourseLevels.departmentLevel' => function ($query) {
                $query->withTrashed();
            },
            'departmentCourses.departmentCourseLevels.departmentLevel.level' => function ($query) {
                $query->withTrashed();
            },
        ])->findOrFail($institutionDepartment->id);
    }

    /**
     * @return array{academicCalendarId: int, modeOfStudyId: int}|null
     */
    private function resolveContext(): ?array
    {
        $academicCalendarId = request()->query('academic_calendar');
        $modeOfStudyId = request()->query('mode_of_study_id');

        if (! $academicCalendarId || ! $modeOfStudyId) {
            return null;
        }

        return [
            'academicCalendarId' => (int) $academicCalendarId,
            'modeOfStudyId' => (int) $modeOfStudyId,
        ];
    }

    /**
     * @return array{classConfig: array<string, array{id: int, students_per_class: int}>, classesCount: array<string, int>, totalnClass: array<string, int>, totalFinalList: array<string, int>}
     */
    private function emptyLookups(): array
    {
        return [
            'classConfig' => [],
            'classesCount' => [],
            'totalnClass' => [],
            'totalFinalList' => [],
        ];
    }

    /**
     * @param  array{academicCalendarId: int, modeOfStudyId: int}  $context
     * @return array{classConfig: array<string, array{id: int, students_per_class: int}>, classesCount: array<string, int>, totalnClass: array<string, int>, totalFinalList: array<string, int>}
     */
    private function buildLookups(InstitutionDepartment $department, array $context): array
    {
        $configs = ClassConfig::query()
            ->where('academic_calendar_id', $context['academicCalendarId'])
            ->where('institution_department_id', $department->id)
            ->where('mode_of_study_id', $context['modeOfStudyId'])
            ->get();

        return [
            'classConfig' => $this->classConfigLookup($configs),
            'classesCount' => $this->classesCountLookup($configs),
            'totalnClass' => $this->totalnClassLookup(
                $context['academicCalendarId'],
                (int) $department->id,
                $context['modeOfStudyId'],
            ),
            'totalFinalList' => $this->totalFinalListLookup((int) $department->id, $context['modeOfStudyId']),
        ];
    }

    /**
     * @param  Collection<int, ClassConfig>  $configs
     * @return array<string, array{id: int, students_per_class: int}>
     */
    private function classConfigLookup(Collection $configs): array
    {
        $lookup = [];

        foreach ($configs as $config) {
            $key = "{$config->department_course_id}_{$config->department_level_id}";
            $lookup[$key] = [
                'students_per_class' => $config->students_per_class ?? 0,
                'id' => $config->id,
            ];
        }

        return $lookup;
    }

    /**
     * @param  Collection<int, ClassConfig>  $configs
     * @return array<string, int>
     */
    private function classesCountLookup(Collection $configs): array
    {
        if ($configs->isEmpty()) {
            return [];
        }

        $countsByConfigId = AcademicCalendarClass::query()
            ->whereIn('class_config_id', $configs->pluck('id'))
            ->whereNull('deleted_at')
            ->select('class_config_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('class_config_id')
            ->pluck('cnt', 'class_config_id');

        $lookup = [];

        foreach ($configs as $config) {
            $key = "{$config->department_course_id}_{$config->department_level_id}";
            $lookup[$key] = (int) ($countsByConfigId[$config->id] ?? 0);
        }

        return $lookup;
    }

    /**
     * @return array<string, int>
     */
    private function totalnClassLookup(int $academicCalendarId, int $departmentId, int $modeOfStudyId): array
    {
        $rows = StudentEnrolment::query()
            ->where('academic_calendar_id', $academicCalendarId)
            ->where('institution_department_id', $departmentId)
            ->where('mode_of_study_id', $modeOfStudyId)
            ->whereNull('deleted_at')
            ->selectRaw('department_course_id, department_level_id, COUNT(*) as total')
            ->groupBy('department_course_id', 'department_level_id')
            ->get();

        $lookup = [];

        foreach ($rows as $row) {
            $key = "{$row->department_course_id}_{$row->department_level_id}";
            $lookup[$key] = (int) $row->total;
        }

        return $lookup;
    }

    /**
     * @return array<string, int>
     */
    private function totalFinalListLookup(int $departmentId, int $modeOfStudyId): array
    {
        $rows = StudentProgram::query()
            ->leftJoin('class_lists', function ($join) {
                $join->on('class_lists.student_program_id', '=', 'student_programs.id')
                    ->where('class_lists.type', ClassListTypeEnum::FINAL->value)
                    ->whereNull('class_lists.deleted_at');
            })
            ->where('student_programs.institution_department_id', $departmentId)
            ->where('student_programs.mode_of_study_id', $modeOfStudyId)
            ->whereNull('student_programs.deleted_at')
            ->select([
                'student_programs.department_course_id',
                'student_programs.department_level_id',
                DB::raw('COUNT(class_lists.id) as total_final_list'),
            ])
            ->groupBy('student_programs.department_course_id', 'student_programs.department_level_id')
            ->get();

        $lookup = [];

        foreach ($rows as $row) {
            $key = "{$row->department_course_id}_{$row->department_level_id}";
            $lookup[$key] = (int) $row->total_final_list;
        }

        return $lookup;
    }

    /**
     * @param  array{classConfig: array<string, array{id: int, students_per_class: int}>, classesCount: array<string, int>, totalnClass: array<string, int>, totalFinalList: array<string, int>}  $lookups
     */
    private function formatDepartment(InstitutionDepartment $department, array $lookups): Collection
    {
        return $department->departmentCourses->map(function (DepartmentCourse $course) use ($department, $lookups) {
            return [
                'institutionDepartmentId' => $department->id,
                'departmentCourseId' => (string) $course->id,
                'courseName' => $course->course->name,
                'levels' => $course->departmentCourseLevels
                    ->map(fn (DepartmentLevelCourse $levelCourse) => $this->formatLevel($course, $levelCourse, $lookups))
                    ->filter()
                    ->values(),
            ];
        })->values();
    }

    /**
     * @param  array{classConfig: array<string, array{id: int, students_per_class: int}>, classesCount: array<string, int>, totalnClass: array<string, int>, totalFinalList: array<string, int>}  $lookups
     * @return array<string, mixed>|null
     */
    private function formatLevel(DepartmentCourse $course, DepartmentLevelCourse $levelCourse, array $lookups): ?array
    {
        $departmentLevel = $levelCourse->departmentLevel;

        if ($departmentLevel === null) {
            return null;
        }

        $level = $departmentLevel->level;

        if ($level === null) {
            return null;
        }

        $key = "{$course->id}_{$levelCourse->department_level_id}";
        $configData = $lookups['classConfig'][$key] ?? null;

        return [
            'departmentLevelId' => (string) $departmentLevel->id,
            'levelName' => $level->name,
            'studentsPerClass' => $configData['students_per_class'] ?? 0,
            'classConfigId' => $configData['id'] ?? null,
            'classesCount' => $lookups['classesCount'][$key] ?? 0,
            'totalnClass' => $lookups['totalnClass'][$key] ?? 0,
            'totalFinalList' => $lookups['totalFinalList'][$key] ?? 0,
        ];
    }
}
