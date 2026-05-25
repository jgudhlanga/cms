<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Students\StudentEnrolment;
use App\Queries\Enrolments\ConfirmedStudentsQuery;
use App\Services\AcademicCalendars\ResolveAcademicYearOptionFromCalendarYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DepartmentAcademicCalendarController extends Controller
{
    /** @var array<int, string|null> */
    private array $academicYearOptionNameById = [];

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
            'resolvedAcademicYearOptionId' => $context['academicYearOptionId'],
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
     * @return array{calendarYear: string, academicCalendarId: int, modeOfStudyId: int, calendarIdsForYear: list<int>, academicYearOptionId: int|null}|null
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
        $academicYearOptionId = app(ResolveAcademicYearOptionFromCalendarYear::class)
            ->resolveAcademicYearOptionId($academicYear);

        return [
            'calendarYear' => $academicYear,
            'academicCalendarId' => (int) $resolvedId,
            'modeOfStudyId' => (int) $modeOfStudyId,
            'calendarIdsForYear' => $calendarIdsForYear,
            'academicYearOptionId' => $academicYearOptionId,
        ];
    }

    /**
     * @return array{calendarYear: null, classConfig: array<string, array{id: int, students_per_class: int, academicYearOptionId: int|null, academicYearOption: string|null, courseSyllabusIds: list<int>, courseSyllabusCodes: list<string>}>, classesCount: array<string, int>, totalnClass: array<string, int>, totalFinalList: array<string, int>}
     */
    private function emptyLookups(): array
    {
        return [
            'calendarYear' => null,
            'classConfig' => [],
            'classesCount' => [],
            'totalnClass' => [],
            'totalFinalList' => [],
        ];
    }

    /**
     * @param  array{calendarYear: string, academicCalendarId: int, modeOfStudyId: int, calendarIdsForYear: list<int>, academicYearOptionId: int|null}  $context
     * @return array{calendarYear: string, classConfig: array<string, array{id: int, students_per_class: int, academicYearOptionId: int|null, academicYearOption: string|null, courseSyllabusIds: list<int>, courseSyllabusCodes: list<string>}>, classesCount: array<string, int>, totalnClass: array<string, int>, totalFinalList: array<string, int>}
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
            ->with('academicYearOption')
            ->get();

        return [
            'calendarYear' => $context['calendarYear'],
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
        $resolver = app(ResolveAcademicYearOptionFromCalendarYear::class);

        $existingKeySet = array_flip(
            ClassConfig::query()
                ->where('calendar_year', $calendarYear)
                ->where('institution_department_id', $department->id)
                ->where('mode_of_study_id', $modeOfStudyId)
                ->get()
                ->map(fn (ClassConfig $c): string => $this->courseLevelOptionLookupKey(
                    (int) $c->department_course_id,
                    (int) $c->department_level_id,
                    $c->academic_year_option_id !== null ? (int) $c->academic_year_option_id : null,
                ))
                ->all(),
        );

        $validPairs = DB::table('department_level_courses as dlc')
            ->join('department_courses as dc', 'dc.id', '=', 'dlc.department_course_id')
            ->join('department_levels as dl', 'dl.id', '=', 'dlc.department_level_id')
            ->join('levels as l', 'l.id', '=', 'dl.level_id')
            ->where('dc.institution_department_id', $department->id)
            ->whereNull('dc.deleted_at')
            ->whereNull('dl.deleted_at')
            ->whereNull('l.deleted_at')
            ->select(['dlc.department_course_id', 'dlc.department_level_id', 'l.calendar_type'])
            ->get();

        DB::transaction(function () use ($validPairs, $existingKeySet, $confirmedCounts, $calendarYear, $department, $modeOfStudyId, $resolver): void {
            foreach ($validPairs as $pair) {
                $calendarType = AcademicCalendarTypeEnum::tryFrom((string) $pair->calendar_type)
                    ?? AcademicCalendarTypeEnum::SEMESTER;

                $resolvedOptionId = $resolver->resolveForCalendarType($calendarYear, $calendarType);

                if ($resolvedOptionId === null) {
                    continue;
                }

                $pairKey = "{$pair->department_course_id}_{$pair->department_level_id}";
                $count = $confirmedCounts[$pairKey] ?? 0;

                $lookupKey = $this->courseLevelOptionLookupKey(
                    (int) $pair->department_course_id,
                    (int) $pair->department_level_id,
                    $resolvedOptionId,
                );

                if ($count <= 0 || isset($existingKeySet[$lookupKey])) {
                    continue;
                }

                ClassConfig::firstOrCreate(
                    [
                        'calendar_year' => $calendarYear,
                        'academic_year_option_id' => $resolvedOptionId,
                        'institution_department_id' => $department->id,
                        'department_course_id' => $pair->department_course_id,
                        'department_level_id' => $pair->department_level_id,
                        'mode_of_study_id' => $modeOfStudyId,
                    ],
                    ['students_per_class' => $count],
                );

                $existingKeySet[$lookupKey] = true;
            }
        });
    }

    private function courseLevelOptionLookupKey(int $departmentCourseId, int $departmentLevelId, ?int $academicYearOptionId): string
    {
        $suffix = $academicYearOptionId === null ? 'none' : (string) $academicYearOptionId;

        return "{$departmentCourseId}_{$departmentLevelId}_{$suffix}";
    }

    /**
     * @param  Collection<int, ClassConfig>  $configs
     * @return array<string, array{id: int, students_per_class: int, academicYearOptionId: int|null, academicYearOption: string|null, courseSyllabusIds: list<int>, courseSyllabusCodes: list<string>}>
     */
    private function classConfigLookup(Collection $configs): array
    {
        $allSyllabusIds = [];
        foreach ($configs as $config) {
            foreach ($config->course_syllabus_ids ?? [] as $sid) {
                $intId = (int) $sid;
                if ($intId > 0) {
                    $allSyllabusIds[] = $intId;
                }
            }
        }
        $allSyllabusIds = array_values(array_unique($allSyllabusIds));
        $codeById = $allSyllabusIds === []
            ? []
            : CourseSyllabus::query()->whereIn('id', $allSyllabusIds)->pluck('code', 'id')->all();

        $lookup = [];

        foreach ($configs as $config) {
            $key = $this->courseLevelOptionLookupKey(
                (int) $config->department_course_id,
                (int) $config->department_level_id,
                $config->academic_year_option_id !== null ? (int) $config->academic_year_option_id : null,
            );
            $optionId = $config->academic_year_option_id !== null ? (int) $config->academic_year_option_id : null;
            $syllabusIds = array_values(array_unique(array_filter(
                array_map(static fn ($id): int => (int) $id, $config->course_syllabus_ids ?? []),
                static fn (int $id): bool => $id > 0,
            )));
            $codesOrdered = [];
            foreach ($syllabusIds as $sid) {
                if (isset($codeById[$sid])) {
                    $codesOrdered[] = $codeById[$sid];
                }
            }
            $lookup[$key] = [
                'students_per_class' => $config->students_per_class ?? 0,
                'id' => $config->id,
                'academicYearOptionId' => $optionId,
                'academicYearOption' => $config->academicYearOption?->name,
                'courseSyllabusIds' => $syllabusIds,
                'courseSyllabusCodes' => $codesOrdered,
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
            ->whereHas('studentEnrolments', function ($query): void {
                $query->whereNull('deleted_at');
            })
            ->select('class_config_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('class_config_id')
            ->pluck('cnt', 'class_config_id');

        $lookup = [];

        foreach ($configs as $config) {
            $key = $this->courseLevelOptionLookupKey(
                (int) $config->department_course_id,
                (int) $config->department_level_id,
                $config->academic_year_option_id !== null ? (int) $config->academic_year_option_id : null,
            );
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
     * @param  array{calendarYear: string|null, classConfig: array<string, array{id: int, students_per_class: int, academicYearOptionId: int|null, academicYearOption: string|null, courseSyllabusIds: list<int>, courseSyllabusCodes: list<string>}>, classesCount: array<string, int>, totalnClass: array<string, int>, totalFinalList: array<string, int>}  $lookups
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
     * @param  array{calendarYear: string|null, classConfig: array<string, array{id: int, students_per_class: int, academicYearOptionId: int|null, academicYearOption: string|null, courseSyllabusIds: list<int>, courseSyllabusCodes: list<string>}>, classesCount: array<string, int>, totalnClass: array<string, int>, totalFinalList: array<string, int>}  $lookups
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

        $calendarYear = $lookups['calendarYear'] ?? null;
        $calendarType = $level->calendar_type instanceof AcademicCalendarTypeEnum
            ? $level->calendar_type
            : AcademicCalendarTypeEnum::tryFrom((string) $level->calendar_type) ?? AcademicCalendarTypeEnum::SEMESTER;

        $resolvedOptionId = is_string($calendarYear) && $calendarYear !== ''
            ? app(ResolveAcademicYearOptionFromCalendarYear::class)->resolveForCalendarType($calendarYear, $calendarType)
            : null;

        $candidateOptionIds = [];
        if ($resolvedOptionId !== null) {
            $candidateOptionIds[] = $resolvedOptionId;
        }
        $candidateOptionIds[] = null;

        $configKey = $this->courseLevelOptionLookupKey(
            (int) $course->id,
            (int) $levelCourse->department_level_id,
            $resolvedOptionId,
        );
        $configData = null;

        foreach ($candidateOptionIds as $optionId) {
            $tryKey = $this->courseLevelOptionLookupKey(
                (int) $course->id,
                (int) $levelCourse->department_level_id,
                $optionId,
            );
            if (isset($lookups['classConfig'][$tryKey])) {
                $configKey = $tryKey;
                $configData = $lookups['classConfig'][$tryKey];
                break;
            }
        }

        $academicYearOptionId = $configData !== null
            ? $configData['academicYearOptionId']
            : $resolvedOptionId;

        $academicYearOption = $configData !== null
            ? ($configData['academicYearOption'] ?? $this->academicYearOptionName($configData['academicYearOptionId']))
            : $this->academicYearOptionName($resolvedOptionId);

        $pairKey = "{$course->id}_{$levelCourse->department_level_id}";

        return [
            'departmentLevelId' => (string) $departmentLevel->id,
            'levelName' => $level->name,
            'calendarType' => $calendarType->value,
            'studentsPerClass' => $configData !== null ? (int) ($configData['students_per_class'] ?? 0) : 0,
            'classConfigId' => $configData !== null ? ($configData['id'] ?? null) : null,
            'classesCount' => (int) ($lookups['classesCount'][$configKey] ?? 0),
            'totalnClass' => (int) ($lookups['totalnClass'][$pairKey] ?? 0),
            'totalFinalList' => (int) ($lookups['totalFinalList'][$pairKey] ?? 0),
            'academicYearOption' => $academicYearOption,
            'academicYearOptionId' => $academicYearOptionId,
            'courseSyllabusIds' => $configData !== null ? ($configData['courseSyllabusIds'] ?? []) : [],
            'courseSyllabusCodes' => $configData !== null ? ($configData['courseSyllabusCodes'] ?? []) : [],
        ];
    }

    private function academicYearOptionName(?int $id): ?string
    {
        if ($id === null) {
            return null;
        }

        if (! array_key_exists($id, $this->academicYearOptionNameById)) {
            $this->academicYearOptionNameById[$id] = AcademicYearOption::query()->whereKey($id)->value('name');
        }

        return $this->academicYearOptionNameById[$id];
    }
}
