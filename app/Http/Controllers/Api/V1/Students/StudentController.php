<?php

namespace App\Http\Controllers\Api\V1\Students;

use App\Http\Resources\Enrolments\EnrolmentResource;
use App\Http\Resources\Shared\AddressResource;
use App\Http\Resources\Shared\ContactResource;
use App\Http\Resources\Shared\NextOfKinResource;
use App\Http\Resources\Students\SponsorResource;
use App\Http\Resources\Students\StudentResource;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Repositories\Students\interface\IStudentRepository;
use App\Traits\HttpUtil;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StudentController
{
    use HttpUtil;

    public function __construct(protected IStudentRepository $repository) {}

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

        $programme = $enrolments
            ->groupBy('student_program_id')
            ->map(function (Collection $programmeEnrolments) use ($modulesBySyllabusId) {
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
                    'semesters' => $sortedEnrolments
                        ->map(fn (StudentEnrolment $enrolment) => $this->mapSemesterEnrolment(
                            $enrolment,
                            $studentProgram?->id,
                            $modulesBySyllabusId
                        ))
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();

        return $this->success($programme);
    }

    /**
     * @param  Collection<int, CourseSyllabusModule>  $modulesBySyllabusId
     * @return array<string, mixed>
     */
    private function mapSemesterEnrolment(
        StudentEnrolment $enrolment,
        ?int $studentProgramId,
        Collection $modulesBySyllabusId,
    ): array {
        $syllabusIds = $this->resolveCourseSyllabusIds($enrolment);

        $enrolmentOptionId = (int) $enrolment->academic_year_option_id;

        $modules = collect($syllabusIds)
            ->flatMap(fn (int $syllabusId) => $modulesBySyllabusId->get($syllabusId, collect()))
            ->filter(fn (CourseSyllabusModule $module): bool => (int) $module->academic_year_option_id === $enrolmentOptionId)
            ->map(fn (CourseSyllabusModule $module): array => [
                'code' => $module->code,
                'name' => $module->title,
                'durationInHours' => $module->duration_in_hours,
                'grade' => null,
                'score' => null,
                'lecturer' => null,
                'type' => null,
                'assessment' => null,
            ])
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
            'module' => $modules,
        ];
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
