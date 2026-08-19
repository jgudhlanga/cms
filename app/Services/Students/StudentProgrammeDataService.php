<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Students\StudentExamResultComment;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\AcademicCalendars\Semester;
use App\Models\Examinations\ExaminationResult;
use App\Models\Institution\AssessmentType;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Services\AcademicCalendars\CourseWorkAggregationService;
use App\Support\AcademicCalendars\AcademicCalendarPeriodResolver;
use App\Support\Institution\CourseSyllabusModulePeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StudentProgrammeDataService
{
    public function __construct(
        protected CourseWorkAggregationService $aggregationService,
        protected CourseSyllabusCodeResolver $courseSyllabusCodeResolver,
        protected StudentEnrolmentProgressionService $progression,
        protected ExamResultEnrolmentStatusResolver $examResultResolver,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function buildProgrammesForStudent(Student $student): array
    {
        $student->load([
            'enrolments.studentApplication',
            'enrolments.departmentLevel.level',
            'enrolments.departmentCourse.course',
            'enrolments.semester',
            'enrolments.academicCalendar',
            'enrolments.studentEnrolmentStatus',
            'enrolments.academicCalendarStudentEnrolment.academicCalendarClass.classConfig',
        ]);

        $enrolments = $student->enrolments
            ->sortBy(fn (StudentEnrolment $enrolment) => $enrolment->academicCalendar?->opening_date ?? '')
            ->values();

        $syllabusIds = $enrolments
            ->groupBy('student_application_id')
            ->flatMap(fn (Collection $programmeEnrolments): array => $this->collectSyllabusIdsForProgramme($programmeEnrolments))
            ->unique()
            ->values()
            ->all();

        $modulesBySyllabusId = $syllabusIds === []
            ? collect()
            : CourseSyllabusModule::query()
                ->whereIn('course_syllabus_id', $syllabusIds)
                ->get()
                ->groupBy('course_syllabus_id');

        $enrolmentIds = $enrolments->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();

        $marksByKey = $enrolmentIds === []
            ? collect()
            : CourseWorkMark::query()
                ->whereIn('student_enrolment_id', $enrolmentIds)
                ->get()
                ->groupBy(fn (CourseWorkMark $mark): string => $this->courseWorkMarkKey(
                    (int) $mark->student_enrolment_id,
                    (int) $mark->course_syllabus_module_id,
                    $mark->assessment_type_id !== null ? (int) $mark->assessment_type_id : null,
                ));

        $assessmentTypesByModeId = $this->assessmentTypesByModeOfStudy(
            $enrolments->pluck('mode_of_study_id')->map(fn (mixed $id): int => (int) $id)->unique()->filter()->values()->all(),
        );

        $studentTenantId = (int) ($student->tenant_id ?? 0);

        $programme = $enrolments
            ->groupBy('student_application_id')
            ->map(function (Collection $programmeEnrolments) use (
                $modulesBySyllabusId,
                $marksByKey,
                $assessmentTypesByModeId,
                $studentTenantId,
            ) {
                $sortedEnrolments = $programmeEnrolments
                    ->sortBy(fn (StudentEnrolment $enrolment) => $enrolment->academicCalendar?->opening_date ?? '')
                    ->values();
                $studentApplication = $sortedEnrolments->first()?->studentApplication;
                $latestEnrolment = $sortedEnrolments->sortByDesc(
                    fn (StudentEnrolment $enrolment) => $enrolment->academicCalendar?->opening_date ?? ''
                )->first();
                $level = $latestEnrolment?->departmentLevel?->level;

                return [
                    'id' => (string) ($studentApplication?->id ?? ''),
                    'level' => $level?->name,
                    'course' => $latestEnrolment?->departmentCourse?->course?->name,
                    'courseCode' => $this->courseSyllabusCodeResolver->resolve($latestEnrolment),
                    'calendarYear' => $latestEnrolment?->academicCalendar?->calendar_year,
                    'isActive' => false,
                    'semesters' => $this->buildSemestersForProgramme(
                        $sortedEnrolments,
                        $modulesBySyllabusId,
                        $marksByKey,
                        $assessmentTypesByModeId,
                        $studentTenantId,
                    ),
                ];
            })
            ->values()
            ->all();

        return $this->markActiveProgramme($programme);
    }

    /**
     * @param  Collection<int, StudentEnrolment>  $programmeEnrolments
     * @return list<int>
     */
    private function collectSyllabusIdsForProgramme(Collection $programmeEnrolments): array
    {
        $latestEnrolment = $programmeEnrolments
            ->sortByDesc(fn (StudentEnrolment $enrolment) => $enrolment->academicCalendar?->opening_date ?? '')
            ->first();

        if ($latestEnrolment === null) {
            return [];
        }

        $calendarType = $latestEnrolment->departmentLevel?->level?->calendar_type;
        $calendarYear = $latestEnrolment->academicCalendar?->calendar_year;
        $studentApplication = $latestEnrolment->studentApplication;

        if (! $calendarType instanceof AcademicCalendarTypeEnum || ! is_string($calendarYear) || $calendarYear === '') {
            return $programmeEnrolments
                ->flatMap(fn (StudentEnrolment $enrolment): array => $this->courseSyllabusCodeResolver->resolveSyllabusIds($enrolment))
                ->unique()
                ->values()
                ->all();
        }

        $enrolmentsBySemesterId = $programmeEnrolments->keyBy('semester_id');
        $phaseOptions = $this->progression->phaseOptions($calendarType);

        return $phaseOptions
            ->flatMap(function (Semester $phase) use (
                $enrolmentsBySemesterId,
                $latestEnrolment,
                $studentApplication,
                $calendarYear,
            ): array {
                $enrolment = $enrolmentsBySemesterId->get((int) $phase->id);

                if ($enrolment instanceof StudentEnrolment) {
                    return $this->courseSyllabusCodeResolver->resolveSyllabusIds($enrolment);
                }

                return $this->resolveSyllabusIdsForPhase(
                    $latestEnrolment,
                    $studentApplication,
                    (int) $phase->id,
                    $calendarYear,
                );
            })
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, StudentEnrolment>  $sortedEnrolments
     * @param  Collection<int, CourseSyllabusModule>  $modulesBySyllabusId
     * @return list<array<string, mixed>>
     */
    private function buildSemestersForProgramme(
        Collection $sortedEnrolments,
        Collection $modulesBySyllabusId,
        Collection $marksByKey,
        Collection $assessmentTypesByModeId,
        int $studentTenantId = 0,
    ): array {
        $studentApplication = $sortedEnrolments->first()?->studentApplication;
        $latestEnrolment = $sortedEnrolments
            ->sortByDesc(fn (StudentEnrolment $enrolment) => $enrolment->academicCalendar?->opening_date ?? '')
            ->first();
        $calendarType = $latestEnrolment?->departmentLevel?->level?->calendar_type;
        $calendarYear = $latestEnrolment?->academicCalendar?->calendar_year;

        if (
            ! $latestEnrolment instanceof StudentEnrolment
            || ! $calendarType instanceof AcademicCalendarTypeEnum
            || ! is_string($calendarYear)
            || $calendarYear === ''
        ) {
            return $sortedEnrolments
                ->map(fn (StudentEnrolment $enrolment) => $this->mapSemesterEnrolment(
                    $enrolment,
                    $studentApplication?->id,
                    $modulesBySyllabusId,
                    $marksByKey,
                    $assessmentTypesByModeId,
                ))
                ->values()
                ->all();
        }

        $phaseOptions = $this->progression->phaseOptions($calendarType);
        $calendars = AcademicCalendar::periodsForYearAndType($calendarYear, $calendarType);
        $slugToCalendar = $calendars->mapWithKeys(
            fn (AcademicCalendar $calendar): array => [
                AcademicCalendarPeriodResolver::semesterSlugForCalendar($calendar) => $calendar,
            ]
        );
        $currentSlug = AcademicCalendarPeriodResolver::currentSemesterSlugForYear($calendarYear, $calendarType);
        $enrolmentsBySemesterId = $sortedEnrolments->keyBy('semester_id');
        $modeOfStudyId = (int) $latestEnrolment->mode_of_study_id;
        $assessmentTypes = $assessmentTypesByModeId->get($modeOfStudyId, collect())->all();

        $studentId = (int) $latestEnrolment->student_id;
        $departmentLevelId = $latestEnrolment->department_level_id !== null
            ? (int) $latestEnrolment->department_level_id
            : null;

        $examMetadataBySlug = $this->examResultResolver->resolveMetadataForLevel(
            $studentId,
            $departmentLevelId,
            $calendarYear,
            $calendarType,
        );

        $availableStatuses = $this->progression->availableStatuses();
        $isLastPhaseSlug = $phaseOptions->last()?->slug;

        // Build phase data in order (ascending) so we can track previous status for progression
        $previousResolvedSlug = null;
        $orderedPhases = [];

        foreach ($phaseOptions as $phase) {
            $phaseSlug = (string) $phase->slug;
            $semesterId = (int) $phase->id;
            $enrolment = $enrolmentsBySemesterId->get($semesterId);
            $calendar = $slugToCalendar->first(
                fn (AcademicCalendar $calendar, string $slug): bool => $slug === $phaseSlug
            );
            $isCurrent = $phaseSlug === $currentSlug;

            $examMetadata = $examMetadataBySlug->get($phaseSlug);
            $examComment = is_array($examMetadata) ? ($examMetadata['comment'] ?? null) : null;
            $hasExamResult = $examComment !== null;
            $examResultStatus = $hasExamResult ? $examComment->value : null;

            $enrolmentSlug = $enrolment instanceof StudentEnrolment
                ? $this->progression->statusSlug($enrolment)
                : null;

            $resolvedSlug = $hasExamResult
                ? strtolower($examComment->value)
                : ($isCurrent ? StudentEnrolmentProgressionService::STATUS_ACTIVE : $enrolmentSlug);

            $isDisabled = $previousResolvedSlug !== null
                && StudentEnrolmentProgressionService::isBlockingStatus($previousResolvedSlug);

            $displayStatus = $this->resolveExamDrivenDisplayStatus(
                $isCurrent,
                $hasExamResult,
                $examComment,
                $enrolmentSlug,
                $isDisabled,
            );

            $syllabusIds = $enrolment instanceof StudentEnrolment
                ? $this->courseSyllabusCodeResolver->resolveSyllabusIds($enrolment)
                : $this->resolveSyllabusIdsForPhase(
                    $latestEnrolment,
                    $studentApplication,
                    $semesterId,
                    $calendarYear,
                );

            $studentEnrolmentId = $enrolment instanceof StudentEnrolment ? (int) $enrolment->id : 0;

            $phaseSession = is_array($examMetadata) ? ($examMetadata['session'] ?? null) : null;
            $phaseCandidateNumber = is_array($examMetadata) ? ($examMetadata['candidateNumber'] ?? null) : null;
            $phaseExamGrades = is_string($phaseSession) && $phaseSession !== '' && is_string($phaseCandidateNumber) && $phaseCandidateNumber !== ''
                ? $this->loadExamGradesForCandidateAndSession(
                    $studentTenantId,
                    $phaseCandidateNumber,
                    $phaseSession,
                )
                : collect();

            $modules = collect($syllabusIds)
                ->flatMap(fn (int $syllabusId) => $modulesBySyllabusId->get($syllabusId, collect()))
                ->filter(fn (CourseSyllabusModule $module): bool => CourseSyllabusModulePeriod::matchesPeriod(
                    $module,
                    $semesterId,
                ))
                ->map(fn (CourseSyllabusModule $module): array => $this->mapProgrammeModule(
                    $module,
                    $studentEnrolmentId,
                    $marksByKey,
                    $assessmentTypes,
                    $phaseExamGrades->get((string) $module->code),
                    $phaseSession,
                ))
                ->values()
                ->all();

            $isLastPhase = $phaseSlug === $isLastPhaseSlug;

            $orderedPhases[] = [
                'id' => sprintf('%s-%s', $studentApplication?->id ?? '', Str::slug($phaseSlug)),
                'label' => $phase->name,
                'year' => $calendarYear,
                'status' => $displayStatus,
                'isCurrent' => $isCurrent,
                'isDisabled' => $isDisabled,
                'hasExamResult' => $hasExamResult,
                'examResultStatus' => $examResultStatus,
                'availableStatuses' => $hasExamResult ? [] : $availableStatuses,
                'studentEnrolmentId' => $enrolment instanceof StudentEnrolment ? (int) $enrolment->id : null,
                'canAdvanceToNextPhase' => ! $isLastPhase && $resolvedSlug === StudentEnrolmentProgressionService::STATUS_PROCEED,
                'canCompleteLevel' => $isLastPhase && $resolvedSlug === StudentEnrolmentProgressionService::STATUS_AWARD,
                'canApplyToNextLevel' => $isLastPhase
                    && $resolvedSlug === StudentEnrolmentProgressionService::STATUS_AWARD
                    && $enrolment instanceof StudentEnrolment
                    && $this->progression->hasFurtherDepartmentLevel($enrolment),
                'module' => $modules,
                '_sortOpeningDate' => $calendar?->opening_date ?? '',
                '_sortPhaseNumber' => $this->phaseNumberFromSlug($phaseSlug),
            ];

            $previousResolvedSlug = $resolvedSlug;
        }

        return collect($orderedPhases)
            ->sortByDesc(fn (array $semester): string => sprintf(
                '%s-%04d',
                $semester['_sortOpeningDate'],
                $semester['_sortPhaseNumber'],
            ))
            ->map(fn (array $semester): array => collect($semester)
                ->except(['_sortOpeningDate', '_sortPhaseNumber'])
                ->all())
            ->values()
            ->all();
    }

    /**
     * @return list<int>
     */
    private function resolveSyllabusIdsForPhase(
        StudentEnrolment $referenceEnrolment,
        ?StudentApplication $studentApplication,
        int $semesterId,
        string $calendarYear,
    ): array {
        $classConfig = ClassConfig::query()
            ->where('institution_department_id', $referenceEnrolment->institution_department_id)
            ->where('department_course_id', $referenceEnrolment->department_course_id)
            ->where('department_level_id', $referenceEnrolment->department_level_id)
            ->where('mode_of_study_id', $referenceEnrolment->mode_of_study_id)
            ->where('semester_id', $semesterId)
            ->where('calendar_year', $calendarYear)
            ->first();

        if ($classConfig !== null) {
            $fromClassConfig = array_values(array_unique(array_filter(
                array_map(static fn (mixed $id): int => (int) $id, $classConfig->course_syllabus_ids ?? []),
                static fn (int $id): bool => $id > 0,
            )));

            if ($fromClassConfig !== []) {
                return $fromClassConfig;
            }
        }

        if ($studentApplication instanceof StudentApplication) {
            return $this->courseSyllabusCodeResolver->resolveSyllabusIdsForProgram($studentApplication);
        }

        return [];
    }

    private function resolveExamDrivenDisplayStatus(
        bool $isCurrent,
        bool $hasExamResult,
        ?StudentExamResultComment $examComment,
        ?string $enrolmentSlug,
        bool $isDisabled,
    ): ?string {
        if ($hasExamResult && $examComment !== null) {
            return ucfirst(strtolower($examComment->value));
        }

        if ($isDisabled) {
            return null;
        }

        if ($isCurrent) {
            return 'Active';
        }

        if ($enrolmentSlug !== null && $enrolmentSlug !== StudentEnrolmentProgressionService::STATUS_ACTIVE) {
            $status = StudentEnrolmentStatus::query()->where('slug', $enrolmentSlug)->value('name');

            return $status !== null ? (string) $status : null;
        }

        return null;
    }

    private function phaseNumberFromSlug(string $slug): int
    {
        $parts = explode('-', $slug);

        return (int) end($parts);
    }

    /**
     * @param  Collection<int, CourseSyllabusModule>  $modulesBySyllabusId
     * @return array<string, mixed>
     */
    private function mapSemesterEnrolment(
        StudentEnrolment $enrolment,
        ?int $studentApplicationId,
        Collection $modulesBySyllabusId,
        Collection $marksByKey,
        Collection $assessmentTypesByModeId,
    ): array {
        $syllabusIds = $this->courseSyllabusCodeResolver->resolveSyllabusIds($enrolment);

        $enrolmentOptionId = (int) $enrolment->semester_id;
        $studentEnrolmentId = (int) $enrolment->id;
        $assessmentTypes = $assessmentTypesByModeId->get((int) $enrolment->mode_of_study_id, collect())->all();

        $modules = collect($syllabusIds)
            ->flatMap(fn (int $syllabusId) => $modulesBySyllabusId->get($syllabusId, collect()))
            ->filter(fn (CourseSyllabusModule $module): bool => CourseSyllabusModulePeriod::matchesPeriod(
                $module,
                $enrolmentOptionId,
            ))
            ->map(fn (CourseSyllabusModule $module): array => $this->mapProgrammeModule(
                $module,
                $studentEnrolmentId,
                $marksByKey,
                $assessmentTypes,
            ))
            ->values()
            ->all();

        $semesterSlug = Str::slug(
            $enrolment->semester?->slug
            ?? $enrolment->semester?->name
            ?? ''
        );

        return [
            'id' => sprintf('%s-%s', $studentApplicationId ?? '', $semesterSlug),
            'label' => $enrolment->semester?->name,
            'year' => $enrolment->academicCalendar?->calendar_year,
            'status' => $enrolment->studentEnrolmentStatus?->name,
            'isCurrent' => false,
            'isDisabled' => false,
            'hasExamResult' => false,
            'examResultStatus' => null,
            'availableStatuses' => $this->progression->availableStatuses(),
            'studentEnrolmentId' => $studentEnrolmentId,
            'canAdvanceToNextPhase' => $this->progression->canAdvanceToNextPhase($enrolment),
            'canCompleteLevel' => $this->progression->canCompleteLevel($enrolment),
            'canApplyToNextLevel' => $this->progression->canApplyToNextLevel($enrolment),
            'module' => $modules,
        ];
    }

    /**
     * @param  list<array{id: int, name: string, description: string|null, weightPercent: int|null}>  $assessmentTypes
     * @return array<string, mixed>
     */
    private function mapProgrammeModule(
        CourseSyllabusModule $module,
        int $studentEnrolmentId,
        Collection $marksByKey,
        array $assessmentTypes,
        ?string $examGrade = null,
        ?string $examSession = null,
    ): array {
        if ($module->capture_mark_only) {
            $saved = $marksByKey->get($this->courseWorkMarkKey($studentEnrolmentId, (int) $module->id, null))?->first();

            return [
                'id' => $module->id,
                'code' => $module->code,
                'name' => $module->title,
                'durationInHours' => $module->duration_in_hours,
                'grade' => $examGrade,
                'examSession' => $examSession,
                'score' => $saved?->mark,
                'lecturer' => null,
                'type' => null,
                'assessment' => null,
                'captureMarkOnly' => true,
                'courseWork' => [
                    'moduleMark' => [
                        'markId' => $saved?->id,
                        'mark' => $saved?->mark,
                        'remark' => $saved?->remark,
                        'isComplete' => $saved?->mark !== null,
                    ],
                ],
            ];
        }

        $assessments = collect($assessmentTypes)->map(function (array $type) use (
            $studentEnrolmentId,
            $module,
            $marksByKey,
        ): array {
            $key = $this->courseWorkMarkKey($studentEnrolmentId, (int) $module->id, (int) $type['id']);
            $saved = $marksByKey->get($key)?->first();

            return [
                'assessmentTypeId' => $type['id'],
                'assessmentTypeName' => $type['name'],
                'markId' => $saved?->id,
                'mark' => $saved?->mark,
                'remark' => $saved?->remark,
            ];
        })->values()->all();

        $aggregation = $this->aggregationService->aggregateStudentModule($assessmentTypes, $assessments);
        $courseWorkTotal60 = $aggregation['courseWorkTotal60'];
        $hasAnyMark = collect($assessments)->contains(fn (array $assessment): bool => $assessment['mark'] !== null);

        return [
            'id' => $module->id,
            'code' => $module->code,
            'name' => $module->title,
            'durationInHours' => $module->duration_in_hours,
            'grade' => $examGrade,
            'examSession' => $examSession,
            'score' => $courseWorkTotal60 !== null
                ? round($courseWorkTotal60 / CourseWorkAggregationService::COURSEWORK_CAP * 100, 1)
                : null,
            'lecturer' => null,
            'type' => null,
            'assessment' => null,
            'captureMarkOnly' => false,
            'courseWork' => $hasAnyMark || $assessmentTypes !== []
                ? [
                    'assessments' => $assessments,
                    'aggregation' => $aggregation,
                ]
                : null,
        ];
    }

    /**
     * Load examination result grades for a candidate and session, keyed by subject_code.
     *
     * @return Collection<string, string>
     */
    private function loadExamGradesForCandidateAndSession(
        int $tenantId,
        string $candidateNumber,
        string $session,
    ): Collection {
        if ($tenantId <= 0 || $candidateNumber === '' || $session === '') {
            return collect();
        }

        return ExaminationResult::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('candidate_number', $candidateNumber)
            ->where('session', $session)
            ->whereNotNull('grade')
            ->get()
            ->keyBy('subject_code')
            ->map(fn (ExaminationResult $row): string => (string) $row->grade);
    }

    /**
     * @param  list<int>  $modeOfStudyIds
     * @return Collection<int, Collection<int, array{id: int, name: string, description: string|null, weightPercent: int|null}>>
     */
    private function assessmentTypesByModeOfStudy(array $modeOfStudyIds): Collection
    {
        return collect($modeOfStudyIds)->mapWithKeys(function (int $modeId): array {
            $types = AssessmentType::query()
                ->whereJsonContains('modes_of_study', $modeId)
                ->orderBy('name')
                ->get()
                ->map(fn (AssessmentType $type): array => [
                    'id' => $type->id,
                    'name' => $type->name,
                    'description' => $type->description,
                    'weightPercent' => $type->weight_percent,
                ])
                ->values();

            return [$modeId => $types];
        });
    }

    /**
     * @param  list<array<string, mixed>>  $programmes
     * @return list<array<string, mixed>>
     */
    private function markActiveProgramme(array $programmes): array
    {
        if ($programmes === []) {
            return [];
        }

        $activeIndex = null;
        $fallbackIndex = null;
        $highestActiveYear = '';
        $highestYear = '';

        foreach ($programmes as $index => $programme) {
            $calendarYear = (string) ($programme['calendarYear'] ?? '');
            $hasActiveSemester = collect($programme['semesters'] ?? [])
                ->contains(fn (array $semester): bool => $this->isActiveEnrolmentStatus($semester['status'] ?? null));

            if ($calendarYear >= $highestYear) {
                $highestYear = $calendarYear;
                $fallbackIndex = $index;
            }

            if ($hasActiveSemester && $calendarYear >= $highestActiveYear) {
                $highestActiveYear = $calendarYear;
                $activeIndex = $index;
            }
        }

        $targetIndex = $activeIndex ?? $fallbackIndex;

        if ($targetIndex === null) {
            return $programmes;
        }

        $programmes[$targetIndex]['isActive'] = true;

        return $programmes;
    }

    private function isActiveEnrolmentStatus(?string $status): bool
    {
        return Str::lower(trim((string) $status)) === 'active';
    }

    private function courseWorkMarkKey(int $studentEnrolmentId, int $moduleId, ?int $assessmentTypeId): string
    {
        if ($assessmentTypeId === null) {
            return sprintf('%d:%d:mark-only', $studentEnrolmentId, $moduleId);
        }

        return sprintf('%d:%d:%d', $studentEnrolmentId, $moduleId, $assessmentTypeId);
    }
}
