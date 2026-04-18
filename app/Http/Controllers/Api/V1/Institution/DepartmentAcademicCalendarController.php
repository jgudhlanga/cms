<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Http\Controllers\Controller;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Students\StudentEnrolment;
use App\Queries\Enrolments\ConfirmedStudentsQuery;
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

        $meta = $context === null ? null : [
            'academicYear' => $context['calendarYear'],
            'resolvedAcademicCalendarId' => $context['academicCalendarId'],
        ];

        return response()->json([
            'data' => $this->formatDepartment($department, $lookups),
            'meta' => $meta,
        ]);
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
     * @return array{calendarYear: string, academicCalendarId: int, modeOfStudyId: int, calendarIdsForYear: list<int>}|null
     */
    private function resolveContext(): ?array
    {
        $academicYear = request()->query('academic_year');
        $modeOfStudyId = request()->query('mode_of_study_id');

        if (! is_string($academicYear) || $academicYear === '' || ! $modeOfStudyId) {
            return null;
        }

        $resolvedId = AcademicCalendar::resolveCanonicalIdForCalendarYear($academicYear);

        if ($resolvedId === null) {
            return null;
        }

        $calendarIdsForYear = AcademicCalendar::idsForStartedCalendarYear($academicYear);

        return [
            'calendarYear' => $academicYear,
            'academicCalendarId' => $resolvedId,
            'modeOfStudyId' => (int) $modeOfStudyId,
            'calendarIdsForYear' => $calendarIdsForYear,
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
     * @param  array{calendarYear: string, academicCalendarId: int, modeOfStudyId: int, calendarIdsForYear: list<int>}  $context
     * @return array{classConfig: array<string, array{id: int, students_per_class: int}>, classesCount: array<string, int>, totalnClass: array<string, int>, totalFinalList: array<string, int>}
     */
    private function buildLookups(InstitutionDepartment $department, array $context): array
    {
        $confirmedCounts = app(ConfirmedStudentsQuery::class)->countsByCourseLevel(
            (int) $department->id,
            $context['modeOfStudyId'],
            $context['calendarYear'],
        );

        $this->seedMissingClassConfigs(
            $department,
            $context['calendarYear'],
            $context['modeOfStudyId'],
            $confirmedCounts,
        );

        $configs = ClassConfig::query()
            ->where('calendar_year', $context['calendarYear'])
            ->where('institution_department_id', $department->id)
            ->where('mode_of_study_id', $context['modeOfStudyId'])
            ->get();

        return [
            'classConfig' => $this->classConfigLookup($configs),
            'classesCount' => $this->classesCountLookup($configs),
            'totalnClass' => $this->totalnClassLookup(
                $context['calendarIdsForYear'],
                (int) $department->id,
                $context['modeOfStudyId'],
            ),
            'totalFinalList' => $confirmedCounts,
        ];
    }

    /**
     * @param  array<string, int>  $confirmedCounts
     */
    private function seedMissingClassConfigs(
        InstitutionDepartment $department,
        string $calendarYear,
        int $modeOfStudyId,
        array $confirmedCounts,
    ): void {
        $existingKeySet = array_flip(
            ClassConfig::query()
                ->where('calendar_year', $calendarYear)
                ->where('institution_department_id', $department->id)
                ->where('mode_of_study_id', $modeOfStudyId)
                ->get()
                ->map(fn (ClassConfig $c) => "{$c->department_course_id}_{$c->department_level_id}")
                ->all(),
        );

        $validPairs = DB::table('department_level_courses as dlc')
            ->join('department_courses as dc', 'dc.id', '=', 'dlc.department_course_id')
            ->where('dc.institution_department_id', $department->id)
            ->whereNull('dc.deleted_at')
            ->select(['dlc.department_course_id', 'dlc.department_level_id'])
            ->get();

        DB::transaction(function () use ($validPairs, $existingKeySet, $confirmedCounts, $calendarYear, $department, $modeOfStudyId): void {
            foreach ($validPairs as $pair) {
                $key = "{$pair->department_course_id}_{$pair->department_level_id}";
                $count = $confirmedCounts[$key] ?? 0;

                if ($count <= 0 || isset($existingKeySet[$key])) {
                    continue;
                }

                ClassConfig::firstOrCreate(
                    [
                        'calendar_year' => $calendarYear,
                        'institution_department_id' => $department->id,
                        'department_course_id' => $pair->department_course_id,
                        'department_level_id' => $pair->department_level_id,
                        'mode_of_study_id' => $modeOfStudyId,
                    ],
                    ['students_per_class' => $count],
                );

                $existingKeySet[$key] = true;
            }
        });
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
     * @param  list<int>  $academicCalendarIds
     * @return array<string, int>
     */
    private function totalnClassLookup(array $academicCalendarIds, int $departmentId, int $modeOfStudyId): array
    {
        if ($academicCalendarIds === []) {
            return [];
        }

        $rows = StudentEnrolment::query()
            ->whereIn('academic_calendar_id', $academicCalendarIds)
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
