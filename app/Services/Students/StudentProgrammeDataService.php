<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Institution\AssessmentType;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Support\Institution\CourseSyllabusModulePeriod;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Services\AcademicCalendars\CourseWorkAggregationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StudentProgrammeDataService
{
    public function __construct(
        protected CourseWorkAggregationService $aggregationService,
        protected CourseSyllabusCodeResolver $courseSyllabusCodeResolver,
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
            'enrolments.academicYearOption',
            'enrolments.academicCalendar',
            'enrolments.studentEnrolmentStatus',
            'enrolments.academicCalendarStudentEnrolment.academicCalendarClass.classConfig',
        ]);

        $enrolments = $student->enrolments
            ->sortBy(fn (StudentEnrolment $enrolment) => $enrolment->academicCalendar?->opening_date ?? '')
            ->values();

        $syllabusIds = $enrolments
            ->flatMap(fn (StudentEnrolment $enrolment) => $this->courseSyllabusCodeResolver->resolveSyllabusIds($enrolment))
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
                    (int) $mark->assessment_type_id,
                ));

        $assessmentTypesByModeId = $this->assessmentTypesByModeOfStudy(
            $enrolments->pluck('mode_of_study_id')->map(fn (mixed $id): int => (int) $id)->unique()->filter()->values()->all(),
        );

        $programme = $enrolments
            ->groupBy('student_application_id')
            ->map(function (Collection $programmeEnrolments) use (
                $modulesBySyllabusId,
                $marksByKey,
                $assessmentTypesByModeId,
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
                    'semesters' => $sortedEnrolments
                        ->map(fn (StudentEnrolment $enrolment) => $this->mapSemesterEnrolment(
                            $enrolment,
                            $studentApplication?->id,
                            $modulesBySyllabusId,
                            $marksByKey,
                            $assessmentTypesByModeId,
                        ))
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();

        return $this->markActiveProgramme($programme);
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

        $enrolmentOptionId = (int) $enrolment->academic_year_option_id;
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
            $enrolment->academicYearOption?->slug
            ?? $enrolment->academicYearOption?->name
            ?? ''
        );

        return [
            'id' => sprintf('%s-%s', $studentApplicationId ?? '', $semesterSlug),
            'label' => $enrolment->academicYearOption?->name,
            'year' => $enrolment->academicCalendar?->calendar_year,
            'status' => $enrolment->studentEnrolmentStatus?->name,
            'studentEnrolmentId' => $studentEnrolmentId,
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
    ): array {
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
            'grade' => null,
            'score' => $courseWorkTotal60 !== null
                ? round($courseWorkTotal60 / CourseWorkAggregationService::COURSEWORK_CAP * 100, 1)
                : null,
            'lecturer' => null,
            'type' => null,
            'assessment' => null,
            'courseWork' => $hasAnyMark || $assessmentTypes !== []
                ? [
                    'assessments' => $assessments,
                    'aggregation' => $aggregation,
                ]
                : null,
        ];
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

    private function courseWorkMarkKey(int $studentEnrolmentId, int $moduleId, int $assessmentTypeId): string
    {
        return sprintf('%d:%d:%d', $studentEnrolmentId, $moduleId, $assessmentTypeId);
    }
}
