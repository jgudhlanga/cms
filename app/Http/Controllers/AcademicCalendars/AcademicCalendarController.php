<?php

namespace App\Http\Controllers\AcademicCalendars;

use App\Enums\Shared\GenderEnum;
use App\Http\Controllers\Concerns\ResolvesAcademicCalendarFromCalendarYear;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicCalendars\AcademicCalendarRequest;
use App\Http\Requests\AcademicCalendars\ClassConfigRequest;
use App\Http\Requests\AcademicCalendars\MoveAcademicCalendarClassStudentsRequest;
use App\Http\Requests\AcademicCalendars\StoreAcademicCalendarClassesRequest;
use App\Http\Resources\AcademicCalendars\AcademicCalendarResource;
use App\Http\Resources\AcademicCalendars\ClassConfigResource;
use App\Http\Resources\Institution\DepartmentCourseResource;
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Http\Resources\Institution\ModeOfStudyResource;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\ModeOfStudy;
use App\Queries\Enrolments\ConfirmedStudentsQuery;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AcademicCalendarController extends Controller
{
    use ResolvesAcademicCalendarFromCalendarYear;

    public function index()
    {
        $this->authorize('viewAny', AcademicCalendar::class);
        $calendars = AcademicCalendar::all();

        return Inertia::render('academicCalendars/Index', [
            'academicCalendars' => AcademicCalendarResource::collection($calendars),
        ]);
    }

    public function store(AcademicCalendarRequest $request)
    {
        AcademicCalendar::create($this->prepareData($request));

        return back()->with('success', 'Academic Calendar created.');
    }

    public function update(AcademicCalendar $academicCalendar, AcademicCalendarRequest $request)
    {
        $academicCalendar->update($this->prepareData($request));

        return back()->with('success', 'Academic Calendar updated.');
    }

    private function prepareData(AcademicCalendarRequest $request): array
    {
        $data = $request->validated();
        $data['opening_date'] = Carbon::parse($data['opening_date'])->format('Y-m-d');
        $data['closing_date'] = Carbon::parse($data['closing_date'])->format('Y-m-d');

        return $data;
    }

    public function departmentAcademicCalendarClasses(InstitutionDepartment $institutionDepartment, string $calendar_year)
    {
        $this->authorize('viewAny', AcademicCalendar::class);

        $academicCalendar = $this->academicCalendarFromCalendarYear($calendar_year);

        $academicCalendars = AcademicCalendar::query()
            ->orderByDesc('calendar_year')
            ->get();
        $departmentLevelId = request()->query('department_level_id');
        $departmentCourseId = request()->query('department_course_id');
        $modeOfStudyId = request()->query('mode_of_study_id');
        $course = DepartmentCourse::find($departmentCourseId);
        $level = DepartmentLevel::find($departmentLevelId);
        $mode = ModeOfStudy::find($modeOfStudyId);
        $classConfigId = request()->query('class_config_id');
        $classConfig = ClassConfig::query()
            ->when($classConfigId, fn ($query) => $query->where('id', $classConfigId))
            ->when(! $classConfigId && $departmentLevelId && $departmentCourseId && $modeOfStudyId, function ($query) use (
                $academicCalendar,
                $institutionDepartment,
                $departmentLevelId,
                $departmentCourseId,
                $modeOfStudyId
            ) {
                $query
                    ->where('calendar_year', $academicCalendar->calendar_year)
                    ->where('institution_department_id', $institutionDepartment->id)
                    ->where('department_level_id', (int) $departmentLevelId)
                    ->where('department_course_id', (int) $departmentCourseId)
                    ->where('mode_of_study_id', (int) $modeOfStudyId)
                    ->whereNull('academic_year_option_id');
            })
            ->first();
        $calendarIdsForYear = AcademicCalendar::idsForStartedCalendarYear((string) $academicCalendar->calendar_year);
        $finalStudentPrograms = $this->resolveFinalStudentPrograms(
            $institutionDepartment,
            $calendarIdsForYear,
            (int) $departmentLevelId,
            (int) $departmentCourseId,
            (int) $modeOfStudyId
        );

        $assignedStudentEnrolmentIds = $this->resolveAssignedStudentEnrolmentIds($classConfig);
        $unassignedFinalStudentPrograms = $this->filterUnassignedFinalStudentPrograms($finalStudentPrograms, $assignedStudentEnrolmentIds);
        $existingClasses = $this->resolveExistingClassesForAllocation($classConfig);
        $classNumberOffset = $this->resolveClassNumberOffset($existingClasses->pluck('name'), $this->resolveClassNamePrefix($level), $mode);
        $previewClasses = $this->buildPreviewClasses(
            $unassignedFinalStudentPrograms,
            (int) ($classConfig?->students_per_class ?? 0),
            $this->resolveClassNamePrefix($level),
            [],
            $mode,
            $classNumberOffset
        );
        $previewClasses = [
            ...$this->resolveExistingClassPreviews($classConfig),
            ...$previewClasses,
        ];
        $context = $this->buildGenerationContext(
            $institutionDepartment,
            $academicCalendar,
            $course,
            $level,
            $mode,
            $classConfig,
            $finalStudentPrograms,
            $unassignedFinalStudentPrograms,
            $existingClasses->count() > 0
        );

        return Inertia::render('institution/academicCalendars/DepartmentAcademicCalendarClasses', [
            'department' => InstitutionDepartmentResource::make($institutionDepartment),
            'academicCalendar' => AcademicCalendarResource::make($academicCalendar),
            'academicCalendars' => AcademicCalendarResource::collection($academicCalendars),
            'course' => DepartmentCourseResource::make($course),
            'level' => DepartmentLevelResource::make($level),
            'mode' => ModeOfStudyResource::make($mode),
            'classConfig' => ClassConfigResource::make($classConfig) ?? null,
            'previewClasses' => $previewClasses,
            'generationContext' => $context,
        ]);
    }

    public function showDepartmentAcademicCalendarClass(
        InstitutionDepartment $institutionDepartment,
        string $calendar_year,
        AcademicCalendarClass $academicCalendarClass
    ): Response {
        $this->authorize('viewAny', AcademicCalendar::class);

        $academicCalendar = $this->academicCalendarFromCalendarYear($calendar_year);

        $academicCalendarClass->loadMissing([
            'classConfig.departmentCourse',
            'classConfig.departmentLevel',
            'classConfig.modeOfStudy',
        ]);

        $classConfig = $academicCalendarClass->classConfig;

        abort_unless(
            $classConfig instanceof ClassConfig
            && (int) $classConfig->institution_department_id === (int) $institutionDepartment->id
            && (string) $classConfig->calendar_year === (string) $academicCalendar->calendar_year,
            404
        );

        $course = $classConfig->departmentCourse ?? DepartmentCourse::query()->find($classConfig->department_course_id);
        $level = $classConfig->departmentLevel ?? DepartmentLevel::query()->find($classConfig->department_level_id);
        $mode = $classConfig->modeOfStudy ?? ModeOfStudy::query()->find($classConfig->mode_of_study_id);

        $students = $this->studentsPayloadForAcademicCalendarClass($academicCalendarClass);

        $siblingClassesCollection = AcademicCalendarClass::query()
            ->where('class_config_id', $classConfig->id)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $toIdNameArray = fn (AcademicCalendarClass $class): array => [
            'id' => $class->id,
            'name' => $class->name,
        ];

        $siblingAcademicCalendarClasses = $siblingClassesCollection
            ->map($toIdNameArray)
            ->values()
            ->all();

        $moveTargetClasses = $siblingClassesCollection
            ->reject(fn (AcademicCalendarClass $class): bool => (int) $class->id === (int) $academicCalendarClass->id)
            ->map($toIdNameArray)
            ->values()
            ->all();

        return Inertia::render('institution/academicCalendars/DepartmentAcademicCalendarClassView', [
            'department' => InstitutionDepartmentResource::make($institutionDepartment),
            'academicCalendar' => AcademicCalendarResource::make($academicCalendar),
            'course' => DepartmentCourseResource::make($course),
            'level' => DepartmentLevelResource::make($level),
            'mode' => ModeOfStudyResource::make($mode),
            'classConfig' => ClassConfigResource::make($classConfig) ?? null,
            'canUpdateAcademicCalendarStudentEnrolments' => auth()->user()?->can('update:academic-calendar-student-enrolments') ?? false,
            'canUpdateAcademicCalendarClass' => auth()->user()?->can('update', $academicCalendar) ?? false,
            'moveTargetClasses' => $moveTargetClasses,
            'siblingAcademicCalendarClasses' => $siblingAcademicCalendarClasses,
            'academicCalendarClass' => [
                'id' => $academicCalendarClass->id,
                'name' => $academicCalendarClass->name,
                'description' => $academicCalendarClass->description,
                'studentCount' => count($students),
                'students' => $students,
            ],
        ]);
    }

    public function moveDepartmentAcademicCalendarClassStudents(
        InstitutionDepartment $institutionDepartment,
        string $calendar_year,
        AcademicCalendarClass $academicCalendarClass,
        MoveAcademicCalendarClassStudentsRequest $request
    ): RedirectResponse {
        $this->authorize('update:academic-calendar-student-enrolments');

        $academicCalendar = $this->academicCalendarFromCalendarYear($calendar_year);

        $academicCalendarClass->loadMissing('classConfig');
        $classConfig = $academicCalendarClass->classConfig;

        abort_unless(
            $classConfig instanceof ClassConfig
            && (int) $classConfig->institution_department_id === (int) $institutionDepartment->id
            && (string) $classConfig->calendar_year === (string) $academicCalendar->calendar_year,
            404
        );

        $validated = $request->validated();
        /** @var array<int, int> $studentEnrolmentIds */
        $studentEnrolmentIds = array_map('intval', $validated['student_enrolment_ids']);
        $targetClassId = (int) $validated['target_academic_calendar_class_id'];

        /** @var \Illuminate\Database\Eloquent\Collection<int, AcademicCalendarStudentEnrolment> $enrollments */
        $enrollments = AcademicCalendarStudentEnrolment::query()
            ->where('academic_calendar_class_id', $academicCalendarClass->id)
            ->whereIn('student_enrolment_id', $studentEnrolmentIds)
            ->whereNull('deleted_at')
            ->get();

        abort_if($enrollments->isEmpty(), 422);

        $this->authorize('update', $enrollments->first());

        DB::transaction(function () use ($enrollments, $targetClassId): void {
            foreach ($enrollments as $enrollment) {
                $enrollment->update([
                    'academic_calendar_class_id' => $targetClassId,
                ]);
            }
        });

        return back()->with('success', __('academic_calendar.move_students_success'));
    }

    public function storePerClassSizeConfig(InstitutionDepartment $institutionDepartment, AcademicCalendar $academicCalendar, ClassConfigRequest $request)
    {
        $validated = $request->validated();

        $lookup = [
            'calendar_year' => $academicCalendar->calendar_year,
            'institution_department_id' => $institutionDepartment->id,
            'academic_year_option_id' => null,
            ...Arr::only($validated, ['department_level_id', 'department_course_id', 'mode_of_study_id']),
        ];

        ClassConfig::updateOrCreate($lookup, [
            'students_per_class' => $validated['students_per_class'],
        ]);

        return back()->with('success', 'Class config successfully saved.');
    }

    public function storeDepartmentAcademicCalendarClasses(
        InstitutionDepartment $institutionDepartment,
        string $calendar_year,
        StoreAcademicCalendarClassesRequest $request
    ) {
        $academicCalendar = $this->academicCalendarFromCalendarYear($calendar_year);

        $this->authorize('update', $academicCalendar);
        $validated = $request->validated();
        $classConfig = ClassConfig::query()
            ->where('id', $validated['class_config_id'])
            ->where('calendar_year', $academicCalendar->calendar_year)
            ->where('institution_department_id', $institutionDepartment->id)
            ->where('department_level_id', $validated['department_level_id'])
            ->where('department_course_id', $validated['department_course_id'])
            ->where('mode_of_study_id', $validated['mode_of_study_id'])
            ->firstOrFail();

        $calendarIdsForYear = AcademicCalendar::idsForStartedCalendarYear((string) $academicCalendar->calendar_year);
        $finalStudentPrograms = $this->resolveFinalStudentPrograms(
            $institutionDepartment,
            $calendarIdsForYear,
            (int) $validated['department_level_id'],
            (int) $validated['department_course_id'],
            (int) $validated['mode_of_study_id']
        );
        $assignedStudentEnrolmentIds = $this->resolveAssignedStudentEnrolmentIds($classConfig);
        $unassignedFinalStudentPrograms = $this->filterUnassignedFinalStudentPrograms($finalStudentPrograms, $assignedStudentEnrolmentIds);
        $level = DepartmentLevel::find((int) $validated['department_level_id']);
        $mode = ModeOfStudy::query()->find((int) $validated['mode_of_study_id']);
        $existingClasses = $this->resolveExistingClassesForAllocation($classConfig);
        $tenantId = function_exists('tenant') ? tenant('id') : null;
        $tenantId = $tenantId ?? auth()->user()?->tenant_id;

        DB::transaction(function () use ($classConfig, $unassignedFinalStudentPrograms, $existingClasses, $validated, $level, $mode, $tenantId): void {
            $remainingStudents = $unassignedFinalStudentPrograms->values();

            foreach ($existingClasses as $existingClass) {
                $remainingSeats = (int) $validated['students_per_class'] - (int) $existingClass->student_count;

                if ($remainingSeats < 1 || $remainingStudents->isEmpty()) {
                    continue;
                }

                $balancedChunk = $this->splitStudentsIntoBalancedChunks($remainingStudents, $remainingSeats)->first();

                if (! $balancedChunk instanceof Collection || $balancedChunk->isEmpty()) {
                    continue;
                }

                foreach ($balancedChunk as $student) {
                    AcademicCalendarStudentEnrolment::query()->create([
                        'tenant_id' => $tenantId,
                        'student_enrolment_id' => (int) $student->student_enrolment_id,
                        'academic_calendar_class_id' => (int) $existingClass->id,
                    ]);
                }

                $remainingStudents = $this->filterUnassignedFinalStudentPrograms(
                    $remainingStudents,
                    $balancedChunk->pluck('student_enrolment_id')->map(fn (mixed $id): int => (int) $id)
                )->values();
            }

            if ($remainingStudents->isEmpty()) {
                return;
            }

            $classNumberOffset = $this->resolveClassNumberOffset($existingClasses->pluck('name'), $this->resolveClassNamePrefix($level), $mode);
            $newPreviewClasses = $this->buildPreviewClasses(
                $remainingStudents,
                (int) $validated['students_per_class'],
                $this->resolveClassNamePrefix($level),
                [],
                $mode,
                $classNumberOffset
            );

            foreach ($newPreviewClasses as $previewClass) {
                $academicClass = AcademicCalendarClass::query()->create([
                    'tenant_id' => $tenantId,
                    'class_config_id' => $classConfig->id,
                    'name' => $previewClass['name'],
                    'description' => "Class - {$previewClass['name']}",
                ]);

                foreach ($previewClass['students'] as $student) {
                    AcademicCalendarStudentEnrolment::query()->create([
                        'tenant_id' => $tenantId,
                        'student_enrolment_id' => $student['studentEnrolmentId'],
                        'academic_calendar_class_id' => $academicClass->id,
                    ]);
                }
            }
        });

        return back()->with('success', __('enrolment.classes_generated_successfully'));
    }

    /**
     * @return list<array{studentEnrolmentId: int, studentId: int, applicationTrackingNumber: mixed, studentNumber: mixed, gender: mixed, name: string}>
     */
    private function studentsPayloadForAcademicCalendarClass(AcademicCalendarClass $academicCalendarClass): array
    {
        return AcademicCalendarStudentEnrolment::query()
            ->join('student_enrolments', 'student_enrolments.id', '=', 'academic_calendar_student_enrolments.student_enrolment_id')
            ->join('student_programs', 'student_programs.id', '=', 'student_enrolments.student_program_id')
            ->join('students', 'students.id', '=', 'student_programs.student_id')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->leftJoin('genders', 'genders.id', '=', 'students.gender_id')
            ->where('academic_calendar_student_enrolments.academic_calendar_class_id', $academicCalendarClass->id)
            ->whereNull('academic_calendar_student_enrolments.deleted_at')
            ->select([
                'student_enrolments.id as student_enrolment_id',
                'student_programs.application_tracking_number',
                'students.student_number',
                'users.id as user_id',
                'genders.title as gender_title',
                'users.first_name',
                'users.last_name',
            ])
            ->orderBy('users.first_name')
            ->orderBy('users.last_name')
            ->get()
            ->map(function (AcademicCalendarStudentEnrolment $row): array {
                return [
                    'studentEnrolmentId' => (int) $row->student_enrolment_id,
                    'studentId' => (int) $row->user_id,
                    'applicationTrackingNumber' => $row->application_tracking_number,
                    'studentNumber' => $row->student_number ?: $row->application_tracking_number,
                    'gender' => $row->gender_title,
                    'name' => trim(sprintf('%s %s', (string) ($row->first_name ?? ''), (string) ($row->last_name ?? ''))),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $academicCalendarIds
     */
    private function resolveFinalStudentPrograms(
        InstitutionDepartment $institutionDepartment,
        array $academicCalendarIds,
        int $departmentLevelId,
        int $departmentCourseId,
        int $modeOfStudyId
    ): Collection {
        return app(ConfirmedStudentsQuery::class)->listForClassAllocation(
            (int) $institutionDepartment->id,
            $departmentLevelId,
            $departmentCourseId,
            $modeOfStudyId,
            $academicCalendarIds,
        );
    }

    private function buildPreviewClasses(
        Collection $finalStudentPrograms,
        int $studentsPerClass,
        string $classNamePrefix = 'Class',
        array $existingClassMap = [],
        ?ModeOfStudy $mode = null,
        int $classNumberOffset = 0
    ): array {
        if ($studentsPerClass < 1 || $finalStudentPrograms->isEmpty()) {
            return [];
        }

        $modeName = $mode instanceof ModeOfStudy ? trim((string) ($mode->name ?? '')) : '';
        $nameBase = $modeName !== ''
            ? $classNamePrefix.' - '.$modeName
            : $classNamePrefix;

        $chunks = $this->mergeTrailingChunkIfBelowHalf(
            $this->splitStudentsIntoBalancedChunks($finalStudentPrograms, $studentsPerClass)->values(),
            $studentsPerClass
        );

        return $chunks
            ->map(function (Collection $chunk, int $index) use ($nameBase, $existingClassMap, $classNumberOffset): array {
                $genderCounts = [
                    'male' => 0,
                    'female' => 0,
                    'unknown' => 0,
                ];
                $className = $nameBase.' - '.($index + 1 + $classNumberOffset);

                foreach ($chunk as $student) {
                    $normalizedGender = $this->normalizeGenderValue($student->gender_title ?? null);

                    if ($normalizedGender === GenderEnum::MALE->value) {
                        $genderCounts['male']++;
                    } elseif ($normalizedGender === GenderEnum::FEMALE->value) {
                        $genderCounts['female']++;
                    } else {
                        $genderCounts['unknown']++;
                    }
                }

                return [
                    'academicCalendarClassId' => $existingClassMap[$className] ?? null,
                    'name' => $className,
                    'studentCount' => $chunk->count(),
                    'genderCounts' => $genderCounts,
                    'students' => $chunk->map(function (mixed $student): array {
                        $firstName = (string) ($student->first_name ?? '');
                        $middleName = (string) ($student->middle_name ?? '');
                        $lastName = (string) ($student->last_name ?? '');

                        return [
                            'studentEnrolmentId' => (int) $student->student_enrolment_id,
                            'studentId' => (int) $student->student_id,
                            'applicationTrackingNumber' => $student->application_tracking_number,
                            'name' => trim($firstName.' '.$middleName.' '.$lastName),
                        ];
                    })->values()->all(),
                ];
            })
            ->all();
    }

    /**
     * @param  Collection<int, Collection<int, mixed>>  $chunks
     * @return Collection<int, Collection<int, mixed>>
     */
    private function mergeTrailingChunkIfBelowHalf(Collection $chunks, int $studentsPerClass): Collection
    {
        $chunks = $chunks->values();

        if ($chunks->count() < 2) {
            return $chunks;
        }

        $last = $chunks->last();

        if (! $last instanceof Collection || $last->count() >= ($studentsPerClass / 2)) {
            return $chunks;
        }

        $count = $chunks->count();
        $penultimate = $chunks->get($count - 2);

        if (! $penultimate instanceof Collection) {
            return $chunks;
        }

        $merged = $penultimate->merge($last)->values();

        return $chunks->take($count - 2)->push($merged)->values();
    }

    private function splitStudentsIntoBalancedChunks(Collection $students, int $studentsPerClass): Collection
    {
        $maleStudents = $students
            ->filter(fn (mixed $student): bool => $this->normalizeGenderValue($student->gender_title ?? null) === GenderEnum::MALE->value)
            ->values();
        $femaleStudents = $students
            ->filter(fn (mixed $student): bool => $this->normalizeGenderValue($student->gender_title ?? null) === GenderEnum::FEMALE->value)
            ->values();
        $unknownGenderStudents = $students
            ->filter(fn (mixed $student): bool => $this->normalizeGenderValue($student->gender_title ?? null) === null)
            ->values();
        $classChunks = collect();

        while ($maleStudents->isNotEmpty() || $femaleStudents->isNotEmpty() || $unknownGenderStudents->isNotEmpty()) {
            $remainingStudents = $maleStudents->count() + $femaleStudents->count() + $unknownGenderStudents->count();
            $capacity = min($studentsPerClass, $remainingStudents);
            $classStudents = collect();
            $hasBothGenders = $maleStudents->isNotEmpty() && $femaleStudents->isNotEmpty();

            if ($hasBothGenders) {
                $maleTarget = (int) floor($capacity / 2);
                $femaleTarget = $capacity - $maleTarget;

                $maleCount = min($maleTarget, $maleStudents->count());
                $femaleCount = min($femaleTarget, $femaleStudents->count());
                $remainingCapacity = $capacity - ($maleCount + $femaleCount);

                if ($remainingCapacity > 0 && $maleStudents->count() > $maleCount) {
                    $extraMaleCount = min($remainingCapacity, $maleStudents->count() - $maleCount);
                    $maleCount += $extraMaleCount;
                    $remainingCapacity -= $extraMaleCount;
                }

                if ($remainingCapacity > 0 && $femaleStudents->count() > $femaleCount) {
                    $extraFemaleCount = min($remainingCapacity, $femaleStudents->count() - $femaleCount);
                    $femaleCount += $extraFemaleCount;
                    $remainingCapacity -= $extraFemaleCount;
                }

                $classStudents = $classStudents
                    ->merge($maleStudents->splice(0, $maleCount))
                    ->merge($femaleStudents->splice(0, $femaleCount));
            }

            if ($classStudents->count() < $capacity) {
                $missingSeats = $capacity - $classStudents->count();

                if ($maleStudents->isNotEmpty()) {
                    $maleFillCount = min($missingSeats, $maleStudents->count());
                    $classStudents = $classStudents->merge($maleStudents->splice(0, $maleFillCount));
                    $missingSeats -= $maleFillCount;
                }

                if ($missingSeats > 0 && $femaleStudents->isNotEmpty()) {
                    $femaleFillCount = min($missingSeats, $femaleStudents->count());
                    $classStudents = $classStudents->merge($femaleStudents->splice(0, $femaleFillCount));
                    $missingSeats -= $femaleFillCount;
                }

                if ($missingSeats > 0 && $unknownGenderStudents->isNotEmpty()) {
                    $classStudents = $classStudents->merge($unknownGenderStudents->splice(0, $missingSeats));
                }
            }

            $classChunks->push($classStudents->values());
        }

        return $classChunks;
    }

    private function normalizeGenderValue(mixed $rawGender): ?string
    {
        $gender = str((string) $rawGender)->lower()->trim()->toString();

        if ($gender === '') {
            return null;
        }

        if (str_contains($gender, strtolower(GenderEnum::FEMALE->value))) {
            return GenderEnum::FEMALE->value;
        }

        if (str_contains($gender, strtolower(GenderEnum::MALE->value))) {
            return GenderEnum::MALE->value;
        }

        return null;
    }

    private function resolveClassNamePrefix(?DepartmentLevel $level): string
    {
        if (! $level instanceof DepartmentLevel) {
            return 'Class';
        }

        $level->loadMissing('level');

        return trim((string) ($level->level?->name ?: 'Class'));
    }

    private function resolveExistingClassMap(?ClassConfig $classConfig): array
    {
        if (! $classConfig instanceof ClassConfig) {
            return [];
        }

        return AcademicCalendarClass::query()
            ->where('class_config_id', $classConfig->id)
            ->pluck('id', 'name')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    private function resolveExistingClassPreviews(?ClassConfig $classConfig): array
    {
        if (! $classConfig instanceof ClassConfig) {
            return [];
        }

        return AcademicCalendarClass::query()
            ->leftJoin('academic_calendar_student_enrolments', function ($join): void {
                $join->on('academic_calendar_student_enrolments.academic_calendar_class_id', '=', 'academic_calandar_classes.id')
                    ->whereNull('academic_calendar_student_enrolments.deleted_at');
            })
            ->leftJoin('student_enrolments', 'student_enrolments.id', '=', 'academic_calendar_student_enrolments.student_enrolment_id')
            ->leftJoin('students', 'students.id', '=', 'student_enrolments.student_id')
            ->leftJoin('genders', 'genders.id', '=', 'students.gender_id')
            ->where('academic_calandar_classes.class_config_id', $classConfig->id)
            ->whereNull('academic_calandar_classes.deleted_at')
            ->groupBy('academic_calandar_classes.id', 'academic_calandar_classes.name')
            ->select([
                'academic_calandar_classes.id',
                'academic_calandar_classes.name',
                DB::raw('COUNT(academic_calendar_student_enrolments.id) as student_count'),
                DB::raw("SUM(CASE WHEN LOWER(genders.title) LIKE 'male%' THEN 1 ELSE 0 END) as male_count"),
                DB::raw("SUM(CASE WHEN LOWER(genders.title) LIKE 'female%' THEN 1 ELSE 0 END) as female_count"),
            ])
            ->orderBy('academic_calandar_classes.id')
            ->get()
            ->map(function (mixed $class): array {
                $maleCount = (int) ($class->male_count ?? 0);
                $femaleCount = (int) ($class->female_count ?? 0);
                $studentCount = (int) ($class->student_count ?? 0);

                return [
                    'academicCalendarClassId' => (int) $class->id,
                    'name' => (string) $class->name,
                    'studentCount' => $studentCount,
                    'genderCounts' => [
                        'male' => $maleCount,
                        'female' => $femaleCount,
                        'unknown' => max(0, $studentCount - ($maleCount + $femaleCount)),
                    ],
                    'students' => [],
                ];
            })
            ->all();
    }

    private function buildGenerationContext(
        InstitutionDepartment $institutionDepartment,
        AcademicCalendar $academicCalendar,
        ?DepartmentCourse $course,
        ?DepartmentLevel $level,
        ?ModeOfStudy $mode,
        ?ClassConfig $classConfig,
        Collection $finalStudentPrograms,
        Collection $unassignedFinalStudentPrograms,
        bool $hasExistingClasses
    ): array {
        $newStudentGenderCounts = [
            'male' => 0,
            'female' => 0,
            'unknown' => 0,
        ];

        foreach ($unassignedFinalStudentPrograms as $student) {
            $normalizedGender = $this->normalizeGenderValue($student->gender_title ?? null);

            if ($normalizedGender === GenderEnum::MALE->value) {
                $newStudentGenderCounts['male']++;
            } elseif ($normalizedGender === GenderEnum::FEMALE->value) {
                $newStudentGenderCounts['female']++;
            } else {
                $newStudentGenderCounts['unknown']++;
            }
        }

        return [
            'institutionDepartmentId' => $institutionDepartment->id,
            'academicCalendarId' => $academicCalendar->id,
            'departmentLevelId' => $level?->id,
            'departmentCourseId' => $course?->id,
            'modeOfStudyId' => $mode?->id,
            'classConfigId' => $classConfig?->id,
            'studentsPerClass' => $classConfig?->students_per_class,
            'finalStudentCount' => $finalStudentPrograms->count(),
            'newFinalStudentCount' => $unassignedFinalStudentPrograms->count(),
            'newStudentGenderCounts' => $newStudentGenderCounts,
            'hasExistingClasses' => $hasExistingClasses,
        ];
    }

    private function resolveAssignedStudentEnrolmentIds(?ClassConfig $classConfig): Collection
    {
        if (! $classConfig instanceof ClassConfig) {
            return collect();
        }

        return AcademicCalendarStudentEnrolment::query()
            ->join('academic_calandar_classes', 'academic_calandar_classes.id', '=', 'academic_calendar_student_enrolments.academic_calendar_class_id')
            ->where('academic_calandar_classes.class_config_id', $classConfig->id)
            ->pluck('academic_calendar_student_enrolments.student_enrolment_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values();
    }

    private function filterUnassignedFinalStudentPrograms(Collection $finalStudentPrograms, Collection $assignedStudentEnrolmentIds): Collection
    {
        if ($assignedStudentEnrolmentIds->isEmpty()) {
            return $finalStudentPrograms->values();
        }

        $assignedLookup = $assignedStudentEnrolmentIds
            ->mapWithKeys(fn (int $id): array => [$id => true])
            ->all();

        return $finalStudentPrograms
            ->reject(fn (mixed $student): bool => isset($assignedLookup[(int) $student->student_enrolment_id]))
            ->values();
    }

    private function resolveExistingClassesForAllocation(?ClassConfig $classConfig): Collection
    {
        if (! $classConfig instanceof ClassConfig) {
            return collect();
        }

        return AcademicCalendarClass::query()
            ->leftJoin('academic_calendar_student_enrolments', function ($join): void {
                $join->on('academic_calendar_student_enrolments.academic_calendar_class_id', '=', 'academic_calandar_classes.id')
                    ->whereNull('academic_calendar_student_enrolments.deleted_at');
            })
            ->where('academic_calandar_classes.class_config_id', $classConfig->id)
            ->whereNull('academic_calandar_classes.deleted_at')
            ->groupBy('academic_calandar_classes.id', 'academic_calandar_classes.name')
            ->select([
                'academic_calandar_classes.id',
                'academic_calandar_classes.name',
                DB::raw('COUNT(academic_calendar_student_enrolments.id) as student_count'),
            ])
            ->orderBy('academic_calandar_classes.id')
            ->get();
    }

    private function resolveClassNumberOffset(Collection $existingClassNames, string $classNamePrefix = 'Class', ?ModeOfStudy $mode = null): int
    {
        if ($existingClassNames->isEmpty()) {
            return 0;
        }

        $modeName = $mode instanceof ModeOfStudy ? trim((string) ($mode->name ?? '')) : '';
        $nameBase = $modeName !== ''
            ? $classNamePrefix.' - '.$modeName
            : $classNamePrefix;
        $pattern = '/^'.preg_quote($nameBase, '/').'\s-\s(\d+)$/';
        $highestClassNumber = 0;

        foreach ($existingClassNames as $existingClassName) {
            $className = (string) $existingClassName;

            if (! preg_match($pattern, $className, $matches)) {
                continue;
            }

            $highestClassNumber = max($highestClassNumber, (int) ($matches[1] ?? 0));
        }

        return $highestClassNumber;
    }
}
