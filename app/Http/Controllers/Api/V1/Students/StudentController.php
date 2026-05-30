<?php

namespace App\Http\Controllers\Api\V1\Students;

use App\Http\Resources\Enrolments\EnrolmentResource;
use App\Http\Resources\Shared\AddressResource;
use App\Http\Resources\Shared\ContactResource;
use App\Http\Resources\Shared\NextOfKinResource;
use App\Http\Resources\Students\SponsorResource;
use App\Http\Resources\Students\StudentResource;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Institution\AssessmentType;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Repositories\Students\interface\IStudentRepository;
use App\Services\AcademicCalendars\CourseWorkAggregationService;
use App\Traits\HttpUtil;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StudentController
{
    use HttpUtil;

    public function __construct(
        protected IStudentRepository $repository,
        protected CourseWorkAggregationService $aggregationService,
    ) {}

    public function index()
    {
        $students = $this->repository->paginateForIndex(
            request()->only([
                'search',
                'name',
                'department',
                'level',
                'course',
                'mode_of_study',
                'academic_year',
                'calendar_type',
                'with_trashed',
            ])
        );

        return StudentResource::collection($students);
    }

    public function studentEnrolements(Student $student)
    {
        abort_unless(request()->user()?->can('view', $student) ?? false, 403);

        $student->load([
            'enrolments.studentProgram',
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
            ->flatMap(fn (StudentEnrolment $enrolment) => $this->resolveCourseSyllabusIds($enrolment))
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
            ->groupBy('student_program_id')
            ->map(function (Collection $programmeEnrolments) use (
                $modulesBySyllabusId,
                $marksByKey,
                $assessmentTypesByModeId,
            ) {
                $sortedEnrolments = $programmeEnrolments
                    ->sortBy(fn (StudentEnrolment $enrolment) => $enrolment->academicCalendar?->opening_date ?? '')
                    ->values();
                $studentProgram = $sortedEnrolments->first()?->studentProgram;
                $latestEnrolment = $sortedEnrolments->sortByDesc(
                    fn (StudentEnrolment $enrolment) => $enrolment->academicCalendar?->opening_date ?? ''
                )->first();
                $level = $latestEnrolment?->departmentLevel?->level;

                return [
                    'id' => (string) ($studentProgram?->id ?? ''),
                    'level' => $level?->name,
                    'course' => $latestEnrolment?->departmentCourse?->course?->name,
                    'courseCode' => $this->resolveCourseCode($latestEnrolment),
                    'calendarYear' => $latestEnrolment?->academicCalendar?->calendar_year,
                    'isActive' => false,
                    'semesters' => $sortedEnrolments
                        ->map(fn (StudentEnrolment $enrolment) => $this->mapSemesterEnrolment(
                            $enrolment,
                            $studentProgram?->id,
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

        return $this->success($this->markActiveProgramme($programme));
    }

    /**
     * @param  Collection<int, CourseSyllabusModule>  $modulesBySyllabusId
     * @return array<string, mixed>
     */
    private function mapSemesterEnrolment(
        StudentEnrolment $enrolment,
        ?int $studentProgramId,
        Collection $modulesBySyllabusId,
        Collection $marksByKey,
        Collection $assessmentTypesByModeId,
    ): array {
        $syllabusIds = $this->resolveCourseSyllabusIds($enrolment);

        $enrolmentOptionId = (int) $enrolment->academic_year_option_id;
        $studentEnrolmentId = (int) $enrolment->id;
        $assessmentTypes = $assessmentTypesByModeId->get((int) $enrolment->mode_of_study_id, collect())->all();

        $modules = collect($syllabusIds)
            ->flatMap(fn (int $syllabusId) => $modulesBySyllabusId->get($syllabusId, collect()))
            ->filter(fn (CourseSyllabusModule $module): bool => (int) $module->academic_year_option_id === $enrolmentOptionId)
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
            'id' => sprintf('%s-%s', $studentProgramId ?? '', $semesterSlug),
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

    /**
     * @return list<int>
     */
    private function resolveCourseSyllabusIds(StudentEnrolment $enrolment): array
    {
        $fromAssignedClass = array_values(array_map(
            'intval',
            array_filter($enrolment->academicCalendarStudentEnrolment
                ?->academicCalendarClass
                ?->classConfig
                ?->course_syllabus_ids ?? [])
        ));

        if ($fromAssignedClass !== []) {
            return $fromAssignedClass;
        }

        $classConfig = ClassConfig::query()
            ->where('department_level_id', $enrolment->department_level_id)
            ->where('department_course_id', $enrolment->department_course_id)
            ->where('academic_year_option_id', $enrolment->academic_year_option_id)
            ->where('mode_of_study_id', $enrolment->mode_of_study_id)
            ->when(
                $enrolment->academicCalendar?->calendar_year,
                fn ($query, string $calendarYear) => $query->where('calendar_year', $calendarYear),
            )
            ->first();

        if ($classConfig !== null) {
            $fromClassConfig = array_values(array_map(
                'intval',
                array_filter($classConfig->course_syllabus_ids ?? [])
            ));

            if ($fromClassConfig !== []) {
                return $fromClassConfig;
            }
        }

        return CourseSyllabus::query()
            ->whereHas('departmentLevelCourse', function ($query) use ($enrolment): void {
                $query
                    ->where('department_level_id', $enrolment->department_level_id)
                    ->where('department_course_id', $enrolment->department_course_id);
            })
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    private function resolveCourseCode(?StudentEnrolment $enrolment): ?string
    {
        if ($enrolment === null) {
            return null;
        }

        $syllabusIds = $this->resolveCourseSyllabusIds($enrolment);

        if ($syllabusIds === []) {
            return null;
        }

        return CourseSyllabus::query()
            ->whereIn('id', $syllabusIds)
            ->orderBy('implementation_year')
            ->value('code');
    }

    // ====== STUDENT ===========
    public function personal(Student $student)
    {
        return StudentResource::make($student);
    }

    public function programs(Student $student)
    {
        return EnrolmentResource::collection($student->programs);
    }

    public function addresses(Student $student)
    {
        return AddressResource::collection($student->addresses);
    }

    public function contacts(Student $student)
    {
        return ContactResource::collection($student->contacts);
    }

    public function sponsors(Student $student)
    {
        return SponsorResource::collection($student->sponsors);
    }

    public function nextOfKin(Student $student)
    {
        return NextOfKinResource::collection($student->nextOfKins);
    }
}
