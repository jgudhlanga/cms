<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Institution\ModeOfStudyEnum;
use App\Http\Controllers\Controller;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\Semester;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\ModeOfStudy;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Students\StudentSemester;
use App\Queries\Enrolments\ConfirmedStudentsQuery;
use App\Support\AcademicCalendars\AcademicCalendarPeriodResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DepartmentAcademicCalendarController extends Controller
{
    /** @var array<int, string|null> */
    private array $semesterNameById = [];

    public function __construct() {}

    public function departmentAcademicCalendar(InstitutionDepartment $institutionDepartment): JsonResponse
    {
        $department = $this->loadDepartmentWithCourses($institutionDepartment);
        $context = $this->resolveContext();

        if ($context === null) {
            return response()->json([
                'data' => $this->formatDepartment($department, $this->emptyLookups()),
                'meta' => null,
            ]);
        }

        $modeTotals = $this->modeTotals($department, $context);
        $meta = [
            'academicYear' => $context['calendarYear'],
            'resolvedAcademicCalendarId' => $context['academicCalendarId'],
            'resolvedSemesterId' => $context['semesterId'],
            'modeTotals' => $modeTotals,
        ];

        if ($context['modeOfStudyId'] === null) {
            return response()->json([
                'data' => [],
                'meta' => $meta,
            ]);
        }

        return response()->json([
            'data' => $this->formatDepartment($department, $this->buildLookups($department, $context)),
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
            'departmentCourses.departmentCourseLevels.programmeSemesters',
        ])->findOrFail($institutionDepartment->id);
    }

    /**
     * @return array{calendarYear: string, academicCalendarId: int, modeOfStudyId: int|null, calendarIdsForYear: list<int>, semesterId: int|null}|null
     */
    private function resolveContext(): ?array
    {
        $academicYear = request()->query('academic_year');

        if (! is_string($academicYear) || $academicYear === '') {
            return null;
        }

        $resolvedId = AcademicCalendar::resolveCanonicalIdForCalendarYear($academicYear);

        if ($resolvedId === null) {
            return null;
        }

        $modeOfStudyIdRaw = request()->query('mode_of_study_id');
        $modeOfStudyId = is_numeric($modeOfStudyIdRaw) ? (int) $modeOfStudyIdRaw : null;
        $semesterIdRaw = request()->query('semester_id');
        $semesterId = is_numeric($semesterIdRaw) ? (int) $semesterIdRaw : null;
        $programmeSemesterIdRaw = request()->query('programme_semester_id');
        $programmeSemesterId = is_numeric($programmeSemesterIdRaw) ? (int) $programmeSemesterIdRaw : null;

        return [
            'calendarYear' => $academicYear,
            'academicCalendarId' => (int) $resolvedId,
            'modeOfStudyId' => $modeOfStudyId !== null && $modeOfStudyId > 0 ? $modeOfStudyId : null,
            'calendarIdsForYear' => AcademicCalendar::idsForStartedCalendarYear($academicYear),
            'semesterId' => $semesterId !== null && $semesterId > 0 ? $semesterId : null,
            'programmeSemesterId' => $programmeSemesterId !== null && $programmeSemesterId > 0 ? $programmeSemesterId : null,
        ];
    }

    /**
     * @param  array{calendarYear: string, academicCalendarId: int, modeOfStudyId: int|null, calendarIdsForYear: list<int>, semesterId: int|null}  $context
     * @return list<array{modeOfStudyId: int, count: int}>
     */
    private function modeTotals(InstitutionDepartment $department, array $context): array
    {
        $counts = app(ConfirmedStudentsQuery::class)->countsByModeForCalendars(
            (int) $department->id,
            $context['calendarIdsForYear'],
        );

        $totals = [];

        foreach ($counts as $modeOfStudyId => $count) {
            $totals[] = [
                'modeOfStudyId' => (int) $modeOfStudyId,
                'count' => (int) $count,
            ];
        }

        return $totals;
    }

    /**
     * @return array{calendarYear: null, classConfig: array<string, array{id: int, students_per_class: int, semesterId: int|null, semester: string|null, courseSyllabusIds: list<int>, courseSyllabusCodes: list<string>}>, configsByPair: array<string, list<array{id: int, students_per_class: int, semesterId: int|null, semester: string|null, courseSyllabusIds: list<int>, courseSyllabusCodes: list<string>}>>, classesCount: array<string, int>, totalnClass: array<string, int>, totalFinalList: array<string, int>, filterSemesterId: int|null, periodsByType: array<string, list<array{id: int, name: string, slug: string}>>, currentSemesterIdByType: array<string, int|null>}
     */
    private function emptyLookups(): array
    {
        return [
            'calendarYear' => null,
            'classConfig' => [],
            'configsByPair' => [],
            'classesCount' => [],
            'totalnClass' => [],
            'totalFinalList' => [],
            'filterSemesterId' => null,
            'periodsByType' => [],
            'currentSemesterIdByType' => [],
            'requireIndustrialAttachment' => false,
        ];
    }

    /**
     * @param  array{calendarYear: string, academicCalendarId: int, modeOfStudyId: int|null, calendarIdsForYear: list<int>, semesterId: int|null}  $context
     * @return array{calendarYear: string, classConfig: array<string, array{id: int, students_per_class: int, semesterId: int|null, semester: string|null, courseSyllabusIds: list<int>, courseSyllabusCodes: list<string>}>, configsByPair: array<string, list<array{id: int, students_per_class: int, semesterId: int|null, semester: string|null, courseSyllabusIds: list<int>, courseSyllabusCodes: list<string>}>>, classesCount: array<string, int>, totalnClass: array<string, int>, totalFinalList: array<string, int>, filterSemesterId: int|null, periodsByType: array<string, list<array{id: int, name: string, slug: string}>>, currentSemesterIdByType: array<string, int|null>}
     */
    private function buildLookups(InstitutionDepartment $department, array $context): array
    {
        $modeOfStudyId = (int) $context['modeOfStudyId'];
        $confirmedCounts = app(ConfirmedStudentsQuery::class)->countsByCourseLevelForCalendars(
            (int) $department->id,
            $modeOfStudyId,
            $context['calendarIdsForYear'],
        );

        $configs = ClassConfig::query()
            ->where('calendar_year', $context['calendarYear'])
            ->where('institution_department_id', $department->id)
            ->where('mode_of_study_id', $modeOfStudyId)
            ->with(['semester', 'programmeSemester'])
            ->get();

        $lookup = $this->classConfigLookup($configs);
        $periodLookups = $this->periodLookups($context['calendarYear']);

        return [
            'calendarYear' => $context['calendarYear'],
            'classConfig' => $lookup,
            'configsByPair' => $this->classConfigsByPair($configs, $lookup),
            'classesCount' => $this->classesCountLookup($configs),
            'totalnClass' => $this->totalnClassLookup(
                $context['calendarIdsForYear'],
                (int) $department->id,
                $modeOfStudyId,
            ),
            'totalFinalList' => $confirmedCounts,
            'filterSemesterId' => $context['semesterId'],
            'filterProgrammeSemesterId' => $context['programmeSemesterId'] ?? null,
            'periodsByType' => $periodLookups['periodsByType'],
            'currentSemesterIdByType' => $periodLookups['currentSemesterIdByType'],
            'requireIndustrialAttachment' => $this->isOjetMode($modeOfStudyId),
        ];
    }

    private function isOjetMode(?int $modeOfStudyId): bool
    {
        if ($modeOfStudyId === null) {
            return false;
        }

        $name = ModeOfStudy::query()->whereKey($modeOfStudyId)->value('name');

        return is_string($name) && ModeOfStudyEnum::tryFromLabel($name) === ModeOfStudyEnum::OJET;
    }

    private function courseLevelOptionLookupKey(int $departmentCourseId, int $departmentLevelId, ?int $semesterId): string
    {
        $suffix = $semesterId === null ? 'none' : (string) $semesterId;

        return "{$departmentCourseId}_{$departmentLevelId}_{$suffix}";
    }

    private function courseLevelPairLookupKey(int $departmentCourseId, int $departmentLevelId): string
    {
        return "{$departmentCourseId}_{$departmentLevelId}";
    }

    /**
     * @param  Collection<int, ClassConfig>  $configs
     * @return array<string, array{id: int, students_per_class: int, semesterId: int|null, semester: string|null, courseSyllabusIds: list<int>, courseSyllabusCodes: list<string>}>
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
                $config->semester_id !== null ? (int) $config->semester_id : null,
            );
            $optionId = $config->semester_id !== null ? (int) $config->semester_id : null;
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
            $entry = [
                'students_per_class' => $config->students_per_class ?? 0,
                'id' => $config->id,
                'semesterId' => $optionId,
                'programmeSemesterId' => $config->programme_semester_id !== null ? (int) $config->programme_semester_id : null,
                'semester' => $config->name ?? $config->programmeSemester?->name ?? $config->semester?->name,
                'courseSyllabusIds' => $syllabusIds,
                'courseSyllabusCodes' => $codesOrdered,
            ];
            $lookup[$key] = $entry;
            $pairKey = $this->courseLevelPairLookupKey(
                (int) $config->department_course_id,
                (int) $config->department_level_id,
            );
            $lookup[$pairKey] ??= $entry;
        }

        return $lookup;
    }

    /**
     * @param  Collection<int, ClassConfig>  $configs
     * @param  array<string, array{id: int, students_per_class: int, semesterId: int|null, semester: string|null, courseSyllabusIds: list<int>, courseSyllabusCodes: list<string>}>  $lookup
     * @return array<string, list<array{id: int, students_per_class: int, semesterId: int|null, semester: string|null, courseSyllabusIds: list<int>, courseSyllabusCodes: list<string>}>>
     */
    private function classConfigsByPair(Collection $configs, array $lookup): array
    {
        $byPair = [];

        foreach ($configs as $config) {
            $key = $this->courseLevelOptionLookupKey(
                (int) $config->department_course_id,
                (int) $config->department_level_id,
                $config->semester_id !== null ? (int) $config->semester_id : null,
            );
            $pairKey = $this->courseLevelPairLookupKey(
                (int) $config->department_course_id,
                (int) $config->department_level_id,
            );
            if (! isset($lookup[$key])) {
                continue;
            }
            $byPair[$pairKey][] = $lookup[$key];
        }

        return $byPair;
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
                $config->semester_id !== null ? (int) $config->semester_id : null,
            );
            $count = (int) ($countsByConfigId[$config->id] ?? 0);
            $lookup[$key] = $count;
            $pairKey = $this->courseLevelPairLookupKey(
                (int) $config->department_course_id,
                (int) $config->department_level_id,
            );
            $lookup[$pairKey] ??= $count;
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

        $rows = StudentSemester::query()
            ->join('student_enrolments', 'student_enrolments.id', '=', 'student_semesters.student_enrolment_id')
            ->whereIn('student_enrolments.academic_calendar_id', $academicCalendarIds)
            ->where('student_enrolments.institution_department_id', $departmentId)
            ->where('student_enrolments.mode_of_study_id', $modeOfStudyId)
            ->whereNull('student_semesters.deleted_at')
            ->whereNull('student_enrolments.deleted_at')
            ->selectRaw('student_enrolments.department_course_id, student_enrolments.department_level_id, student_semesters.semester_id, COUNT(*) as total')
            ->groupBy('student_enrolments.department_course_id', 'student_enrolments.department_level_id', 'student_semesters.semester_id')
            ->get();

        $lookup = [];

        foreach ($rows as $row) {
            $pairKey = "{$row->department_course_id}_{$row->department_level_id}";
            $semesterSuffix = $row->semester_id !== null ? (string) $row->semester_id : 'none';
            $lookup["{$pairKey}_{$semesterSuffix}"] = (int) $row->total;
            $lookup[$pairKey] = (int) ($lookup[$pairKey] ?? 0) + (int) $row->total;
        }

        return $lookup;
    }

    /**
     * @param  array{calendarYear: string|null, classConfig: array<string, array{id: int, students_per_class: int, semesterId: int|null, semester: string|null, courseSyllabusIds: list<int>, courseSyllabusCodes: list<string>}>, configsByPair: array<string, list<array{id: int, students_per_class: int, semesterId: int|null, semester: string|null, courseSyllabusIds: list<int>, courseSyllabusCodes: list<string>}>>, classesCount: array<string, int>, totalnClass: array<string, int>, totalFinalList: array<string, int>, filterSemesterId: int|null, periodsByType: array<string, list<array{id: int, name: string, slug: string}>>, currentSemesterIdByType: array<string, int|null>}  $lookups
     */
    private function formatDepartment(InstitutionDepartment $department, array $lookups): Collection
    {
        return $department->departmentCourses->map(function (DepartmentCourse $course) use ($department, $lookups) {
            return [
                'institutionDepartmentId' => $department->id,
                'departmentCourseId' => (string) $course->id,
                'courseName' => $course->course->name,
                'levels' => $course->departmentCourseLevels
                    ->flatMap(fn (DepartmentLevelCourse $levelCourse) => $this->formatLevels($course, $levelCourse, $lookups))
                    ->filter()
                    ->values(),
            ];
        })->filter(fn (array $courseRow): bool => $courseRow['levels']->isNotEmpty())->values();
    }

    /**
     * @param  array{calendarYear: string|null, classConfig: array<string, array{id: int, students_per_class: int, semesterId: int|null, semester: string|null, courseSyllabusIds: list<int>, courseSyllabusCodes: list<string>}>, configsByPair: array<string, list<array{id: int, students_per_class: int, semesterId: int|null, semester: string|null, courseSyllabusIds: list<int>, courseSyllabusCodes: list<string>}>>, classesCount: array<string, int>, totalnClass: array<string, int>, totalFinalList: array<string, int>, filterSemesterId: int|null, periodsByType: array<string, list<array{id: int, name: string, slug: string}>>, currentSemesterIdByType: array<string, int|null>}  $lookups
     * @return Collection<int, array<string, mixed>>
     */
    private function formatLevels(DepartmentCourse $course, DepartmentLevelCourse $levelCourse, array $lookups): Collection
    {
        $departmentLevel = $levelCourse->departmentLevel;

        if ($departmentLevel === null) {
            return collect();
        }

        $level = $departmentLevel->level;

        if ($level === null) {
            return collect();
        }

        if (($lookups['requireIndustrialAttachment'] ?? false) && ! $levelCourse->includes_industrial_attachment) {
            return collect();
        }

        $calendarType = $level->calendar_type instanceof AcademicCalendarTypeEnum
            ? $level->calendar_type
            : AcademicCalendarTypeEnum::tryFrom((string) $level->calendar_type) ?? AcademicCalendarTypeEnum::SEMESTER;

        $pairKey = $this->courseLevelPairLookupKey(
            (int) $course->id,
            (int) $levelCourse->department_level_id,
        );
        $allConfigRows = $lookups['configsByPair'][$pairKey] ?? [];
        $configuredSemesterIds = [];

        $configuredProgrammeSemesterIds = [];

        foreach ($allConfigRows as $configRow) {
            if (($configRow['programmeSemesterId'] ?? null) !== null) {
                $configuredProgrammeSemesterIds[] = (int) $configRow['programmeSemesterId'];
            } elseif ($configRow['semesterId'] !== null) {
                $configuredSemesterIds[] = (int) $configRow['semesterId'];
            }
        }

        $displayRows = $this->displayConfigRows($allConfigRows, $lookups['filterSemesterId'] ?? null, $calendarType, $lookups);
        $configs = [];

        foreach ($displayRows as $configData) {
            $semesterId = $configData['semesterId'] ?? null;
            $configKey = $this->courseLevelOptionLookupKey(
                (int) $course->id,
                (int) $levelCourse->department_level_id,
                $semesterId,
            );
            $nClassKey = $semesterId !== null ? "{$pairKey}_{$semesterId}" : $pairKey;

            $configs[] = [
                'classConfigId' => $configData['id'],
                'semesterId' => $semesterId,
                'semester' => $configData['semester'] ?? $this->semesterName($semesterId),
                'studentsPerClass' => (int) ($configData['students_per_class'] ?? 0),
                'classesCount' => (int) ($lookups['classesCount'][$configKey] ?? 0),
                'totalnClass' => (int) ($lookups['totalnClass'][$nClassKey] ?? $lookups['totalnClass'][$pairKey] ?? 0),
                'courseSyllabusIds' => $configData['courseSyllabusIds'] ?? [],
                'courseSyllabusCodes' => $configData['courseSyllabusCodes'] ?? [],
            ];
        }

        return collect([
            [
                'departmentLevelId' => (string) $departmentLevel->id,
                'levelName' => $level->name,
                'calendarType' => $calendarType->value,
                'totalFinalList' => (int) ($lookups['totalFinalList'][$pairKey] ?? 0),
                'currentSemesterId' => $lookups['currentSemesterIdByType'][$calendarType->value] ?? null,
                'configs' => $configs,
                'remainingPeriods' => $this->remainingPeriods(
                    $calendarType,
                    $configuredSemesterIds,
                    $configuredProgrammeSemesterIds,
                    $lookups,
                    $levelCourse,
                ),
            ],
        ]);
    }

    /**
     * @param  list<array{id: int, students_per_class: int, semesterId: int|null, semester: string|null, courseSyllabusIds: list<int>, courseSyllabusCodes: list<string>}>  $configRows
     * @param  array{periodsByType: array<string, list<array{id: int, name: string, slug: string}>>}  $lookups
     * @return list<array{id: int, students_per_class: int, semesterId: int|null, semester: string|null, courseSyllabusIds: list<int>, courseSyllabusCodes: list<string>}>
     */
    private function displayConfigRows(array $configRows, ?int $filterSemesterId, AcademicCalendarTypeEnum $calendarType, array $lookups): array
    {
        $rows = $configRows;

        if ($filterSemesterId !== null) {
            $rows = array_values(array_filter(
                $rows,
                static fn (array $row): bool => (int) ($row['semesterId'] ?? 0) === $filterSemesterId,
            ));
        }

        $order = [];
        foreach ($lookups['periodsByType'][$calendarType->value] ?? [] as $index => $period) {
            $order[(int) $period['id']] = $index;
        }

        $currentSemesterId = $lookups['currentSemesterIdByType'][$calendarType->value] ?? null;

        usort($rows, static function (array $left, array $right) use ($order, $currentSemesterId): int {
            $leftId = (int) ($left['semesterId'] ?? 0);
            $rightId = (int) ($right['semesterId'] ?? 0);
            $leftCurrent = $currentSemesterId !== null && $leftId === (int) $currentSemesterId ? 0 : 1;
            $rightCurrent = $currentSemesterId !== null && $rightId === (int) $currentSemesterId ? 0 : 1;

            if ($leftCurrent !== $rightCurrent) {
                return $leftCurrent <=> $rightCurrent;
            }

            return ($order[$leftId] ?? 999) <=> ($order[$rightId] ?? 999);
        });

        return $rows;
    }

    /**
     * @param  list<int>  $configuredSemesterIds
     * @param  list<int>  $configuredProgrammeSemesterIds
     * @param  array{filterSemesterId: int|null, filterProgrammeSemesterId?: int|null, periodsByType: array<string, list<array{id: int, name: string, slug: string}>>, currentSemesterIdByType: array<string, int|null>}  $lookups
     * @return list<array{id: int, name: string, isCurrent: bool, programmeSemesterId?: int|null}>
     */
    private function remainingPeriods(
        AcademicCalendarTypeEnum $calendarType,
        array $configuredSemesterIds,
        array $configuredProgrammeSemesterIds,
        array $lookups,
        DepartmentLevelCourse $levelCourse,
    ): array {
        $levelCourse->loadMissing('programmeSemesters');

        if ($levelCourse->programmeSemesters !== null && $levelCourse->programmeSemesters->isNotEmpty()) {
            $configured = array_fill_keys($configuredProgrammeSemesterIds, true);
            $filterProgrammeSemesterId = $lookups['filterProgrammeSemesterId'] ?? null;
            $remaining = [];

            foreach ($levelCourse->programmeSemesters as $programmeSemester) {
                $programmeSemesterId = (int) $programmeSemester->id;

                if (isset($configured[$programmeSemesterId])) {
                    continue;
                }

                if ($filterProgrammeSemesterId !== null && $programmeSemesterId !== $filterProgrammeSemesterId) {
                    continue;
                }

                $remaining[] = [
                    'id' => $programmeSemesterId,
                    'programmeSemesterId' => $programmeSemesterId,
                    'name' => (string) $programmeSemester->name,
                    'isCurrent' => false,
                ];
            }

            return $remaining;
        }

        $configured = array_fill_keys($configuredSemesterIds, true);
        $currentSemesterId = $lookups['currentSemesterIdByType'][$calendarType->value] ?? null;
        $filterSemesterId = $lookups['filterSemesterId'] ?? null;
        $remaining = [];

        foreach ($lookups['periodsByType'][$calendarType->value] ?? [] as $period) {
            $periodId = (int) $period['id'];

            if (isset($configured[$periodId])) {
                continue;
            }

            if ($filterSemesterId !== null && $periodId !== $filterSemesterId) {
                continue;
            }

            $remaining[] = [
                'id' => $periodId,
                'name' => $period['name'],
                'isCurrent' => $currentSemesterId !== null && $periodId === $currentSemesterId,
            ];
        }

        return $remaining;
    }

    /**
     * @return array{periodsByType: array<string, list<array{id: int, name: string, slug: string}>>, currentSemesterIdByType: array<string, int|null>}
     */
    private function periodLookups(string $calendarYear): array
    {
        $allowedSlugs = [];

        foreach (AcademicCalendarTypeEnum::cases() as $type) {
            foreach ($type->allowedSemesterSlugs() as $slug) {
                $allowedSlugs[] = $slug;
            }
        }

        $semesters = Semester::query()
            ->whereIn('slug', $allowedSlugs)
            ->get(['id', 'name', 'slug']);

        $periodsByType = [];
        $idBySlug = [];

        foreach ($semesters as $semester) {
            $slug = (string) $semester->slug;
            $idBySlug[$slug] = (int) $semester->id;
            $prefix = explode('-', $slug)[0] ?? '';
            $type = AcademicCalendarTypeEnum::tryFrom($prefix);

            if (! $type instanceof AcademicCalendarTypeEnum) {
                continue;
            }

            $periodsByType[$type->value][] = [
                'id' => (int) $semester->id,
                'name' => (string) $semester->name,
                'slug' => $slug,
            ];
        }

        foreach ($periodsByType as $typeValue => $periods) {
            usort($periods, fn (array $left, array $right): int => $this->slugOrdinal($left['slug']) <=> $this->slugOrdinal($right['slug']));
            $periodsByType[$typeValue] = $periods;
        }

        $currentSemesterIdByType = [];

        foreach (AcademicCalendarTypeEnum::cases() as $type) {
            $slug = AcademicCalendarPeriodResolver::currentSemesterSlugForYear($calendarYear, $type);
            $currentSemesterIdByType[$type->value] = $idBySlug[$slug] ?? null;
        }

        return [
            'periodsByType' => $periodsByType,
            'currentSemesterIdByType' => $currentSemesterIdByType,
        ];
    }

    private function slugOrdinal(string $slug): int
    {
        $parts = explode('-', $slug);

        return (int) end($parts);
    }

    private function semesterName(?int $id): ?string
    {
        if ($id === null) {
            return null;
        }

        if (! array_key_exists($id, $this->semesterNameById)) {
            $this->semesterNameById[$id] = Semester::query()->whereKey($id)->value('name');
        }

        return $this->semesterNameById[$id];
    }
}
