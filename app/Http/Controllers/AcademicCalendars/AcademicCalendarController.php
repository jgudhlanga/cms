<?php

namespace App\Http\Controllers\AcademicCalendars;

use App\Enums\AcademicCalendars\ClassMetaDataTypeEnum;
use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\GenderEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicCalendars\AcademicCalendarRequest;
use App\Http\Requests\AcademicCalendars\ClassConfigRequest;
use App\Http\Requests\AcademicCalendars\StoreAcademicCalendarClassesRequest;
use App\Http\Resources\AcademicCalendars\AcademicCalendarOptionResource;
use App\Http\Resources\AcademicCalendars\AcademicCalendarResource;
use App\Http\Resources\AcademicCalendars\ClassConfigResource;
use App\Http\Resources\Institution\DepartmentCourseResource;
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Http\Resources\Institution\IntakePeriodResource;
use App\Http\Resources\Institution\ModeOfStudyResource;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarClassMetaData;
use App\Models\AcademicCalendars\AcademicCalendarOption;
use App\Models\AcademicCalendars\AcademicCalendarStudentProgram;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\ClassMetaDataType;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\ModeOfStudy;
use App\Models\Students\StudentProgram;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AcademicCalendarController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', AcademicCalendar::class);
        $options = AcademicCalendarOption::all();
        $calendars = AcademicCalendar::all();
        $intakePeriods = IntakePeriod::orderBy('end_date', 'desc')->get();

        return Inertia::render('academicCalendars/Index', [
            'academicCalendarOptions' => AcademicCalendarOptionResource::collection($options),
            'academicCalendars' => AcademicCalendarResource::collection($calendars),
            'intakePeriods' => IntakePeriodResource::collection($intakePeriods),
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
        $data['intake_period_ids'] = array_values($data['intake_period_ids'] ?? []);

        return $data;
    }

    public function departmentAcademicCalendarClasses(InstitutionDepartment $institutionDepartment, AcademicCalendar $academicCalendar)
    {
        $this->authorize('viewAny', AcademicCalendar::class);

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
                    ->where('academic_calendar_id', $academicCalendar->id)
                    ->where('institution_department_id', $institutionDepartment->id)
                    ->where('department_level_id', (int) $departmentLevelId)
                    ->where('department_course_id', (int) $departmentCourseId)
                    ->where('mode_of_study_id', (int) $modeOfStudyId);
            })
            ->first();
        $finalStudentPrograms = $this->resolveFinalStudentPrograms(
            $institutionDepartment,
            $academicCalendar,
            (int) $departmentLevelId,
            (int) $departmentCourseId,
            (int) $modeOfStudyId
        );

        $assignedStudentProgramIds = $this->resolveAssignedStudentProgramIds($classConfig);
        $unassignedFinalStudentPrograms = $this->filterUnassignedFinalStudentPrograms($finalStudentPrograms, $assignedStudentProgramIds);
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
        AcademicCalendar $academicCalendar,
        AcademicCalendarClass $academicCalendarClass
    ) {
        $this->authorize('viewAny', AcademicCalendar::class);

        $academicCalendarClass->loadMissing([
            'classConfig.departmentCourse',
            'classConfig.departmentLevel',
            'classConfig.modeOfStudy',
        ]);

        $classConfig = $academicCalendarClass->classConfig;

        abort_unless(
            $classConfig instanceof ClassConfig
            && (int) $classConfig->institution_department_id === (int) $institutionDepartment->id
            && (int) $classConfig->academic_calendar_id === (int) $academicCalendar->id,
            404
        );

        $course = $classConfig->departmentCourse ?? DepartmentCourse::query()->find($classConfig->department_course_id);
        $level = $classConfig->departmentLevel ?? DepartmentLevel::query()->find($classConfig->department_level_id);
        $mode = $classConfig->modeOfStudy ?? ModeOfStudy::query()->find($classConfig->mode_of_study_id);

        $students = AcademicCalendarStudentProgram::query()
            ->join('student_programs', 'student_programs.id', '=', 'academic_calendar_student_programs.student_program_id')
            ->join('students', 'students.id', '=', 'student_programs.student_id')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->leftJoin('genders', 'genders.id', '=', 'students.gender_id')
            ->where('academic_calendar_student_programs.academic_calendar_class_id', $academicCalendarClass->id)
            ->select([
                'student_programs.id as student_program_id',
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
            ->map(function (mixed $student): array {
                return [
                    'studentProgramId' => (int) $student->student_program_id,
                    'studentId' => (int) $student->user_id,
                    'applicationTrackingNumber' => $student->application_tracking_number,
                    'studentNumber' => $student->student_number ?: $student->application_tracking_number,
                    'gender' => $student->gender_title,
                    'name' => trim(sprintf('%s %s', (string) ($student->first_name ?? ''), (string) ($student->last_name ?? ''))),
                ];
            })
            ->values()
            ->all();

        $metadataTypes = ClassMetaDataType::query()
            ->select(['name', 'description'])
            ->orderBy('name')
            ->get();

        $metadata = ($metadataTypes->isNotEmpty()
            ? $metadataTypes
            : collect(ClassMetaDataTypeEnum::cases())->map(
                fn (ClassMetaDataTypeEnum $type): ClassMetaDataType => new ClassMetaDataType([
                    'name' => $type->value,
                    'description' => $type->label(),
                ])
            ))
            ->map(function (ClassMetaDataType $type) use ($academicCalendarClass): array {
                $typeId = (int) $type->id;
                $exists = AcademicCalendarClassMetaData::query()
                    ->when($typeId > 0, fn ($query) => $query->where('class_metadata_type_id', $typeId))
                    ->where('metadatable_type', AcademicCalendarClass::class)
                    ->where('metadatable_id', $academicCalendarClass->id)
                    ->exists();

                return [
                    'key' => $type->name,
                    'label' => $type->description ?: str($type->name)->headline()->toString(),
                    'value' => $exists ? 'Assigned' : 'Not set',
                ];
            })
            ->values()
            ->all();

        return Inertia::render('institution/academicCalendars/DepartmentAcademicCalendarClassView', [
            'department' => InstitutionDepartmentResource::make($institutionDepartment),
            'academicCalendar' => AcademicCalendarResource::make($academicCalendar),
            'course' => DepartmentCourseResource::make($course),
            'level' => DepartmentLevelResource::make($level),
            'mode' => ModeOfStudyResource::make($mode),
            'classConfig' => ClassConfigResource::make($classConfig) ?? null,
            'academicCalendarClass' => [
                'id' => $academicCalendarClass->id,
                'name' => $academicCalendarClass->name,
                'description' => $academicCalendarClass->description,
                'studentCount' => count($students),
                'students' => $students,
                'metadata' => $metadata,
            ],
        ]);
    }

    public function storePerClassSizeConfig(InstitutionDepartment $institutionDepartment, AcademicCalendar $academicCalendar, ClassConfigRequest $request)
    {
        $validated = $request->validated();

        $lookup = [
            'academic_calendar_id' => $academicCalendar->id,
            'institution_department_id' => $institutionDepartment->id,
            ...Arr::only($validated, ['department_level_id', 'department_course_id', 'mode_of_study_id']),
        ];

        ClassConfig::updateOrCreate($lookup, [
            'students_per_class' => $validated['students_per_class'],
        ]);

        return back()->with('success', 'Class config successfully saved.');
    }

    public function storeDepartmentAcademicCalendarClasses(
        InstitutionDepartment $institutionDepartment,
        AcademicCalendar $academicCalendar,
        StoreAcademicCalendarClassesRequest $request
    ) {
        $this->authorize('update', $academicCalendar);
        $validated = $request->validated();
        $classConfig = ClassConfig::query()
            ->where('id', $validated['class_config_id'])
            ->where('academic_calendar_id', $academicCalendar->id)
            ->where('institution_department_id', $institutionDepartment->id)
            ->where('department_level_id', $validated['department_level_id'])
            ->where('department_course_id', $validated['department_course_id'])
            ->where('mode_of_study_id', $validated['mode_of_study_id'])
            ->firstOrFail();

        $finalStudentPrograms = $this->resolveFinalStudentPrograms(
            $institutionDepartment,
            $academicCalendar,
            (int) $validated['department_level_id'],
            (int) $validated['department_course_id'],
            (int) $validated['mode_of_study_id']
        );
        $assignedStudentProgramIds = $this->resolveAssignedStudentProgramIds($classConfig);
        $unassignedFinalStudentPrograms = $this->filterUnassignedFinalStudentPrograms($finalStudentPrograms, $assignedStudentProgramIds);
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
                    AcademicCalendarStudentProgram::query()->create([
                        'tenant_id' => $tenantId,
                        'student_program_id' => (int) $student->student_program_id,
                        'academic_calendar_class_id' => (int) $existingClass->id,
                    ]);
                }

                $remainingStudents = $this->filterUnassignedFinalStudentPrograms(
                    $remainingStudents,
                    $balancedChunk->pluck('student_program_id')->map(fn (mixed $id): int => (int) $id)
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
                    'description' => "Class - {$previewClass['name']} ({$previewClass['studentCount']} students)",
                ]);

                foreach ($previewClass['students'] as $student) {
                    AcademicCalendarStudentProgram::query()->create([
                        'tenant_id' => $tenantId,
                        'student_program_id' => $student['studentProgramId'],
                        'academic_calendar_class_id' => $academicClass->id,
                    ]);
                }
            }
        });

        return back()->with('success', __('enrolment.classes_generated_successfully'));
    }

    private function resolveFinalStudentPrograms(
        InstitutionDepartment $institutionDepartment,
        AcademicCalendar $academicCalendar,
        int $departmentLevelId,
        int $departmentCourseId,
        int $modeOfStudyId
    ): Collection {
        $intakePeriodIds = collect($academicCalendar->intake_period_ids ?? [])
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values()
            ->all();

        return StudentProgram::query()
            ->join('class_lists', 'class_lists.student_program_id', '=', 'student_programs.id')
            ->join('students', 'students.id', '=', 'student_programs.student_id')
            ->leftJoin('genders', 'genders.id', '=', 'students.gender_id')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->where('student_programs.institution_department_id', $institutionDepartment->id)
            ->where('student_programs.department_level_id', $departmentLevelId)
            ->where('student_programs.department_course_id', $departmentCourseId)
            ->where('student_programs.mode_of_study_id', $modeOfStudyId)
            ->whereIn('student_programs.intake_period_id', $intakePeriodIds)
            ->where('class_lists.type', ClassListTypeEnum::FINAL->value)
            ->whereNull('class_lists.deleted_at')
            ->select([
                'student_programs.id as student_program_id',
                'student_programs.student_id',
                'student_programs.application_tracking_number',
                'genders.title as gender_title',
                'users.first_name',
                'users.middle_name',
                'users.last_name',
            ])
            ->orderBy('users.first_name')
            ->orderBy('users.last_name')
            ->get();
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

        return $this->splitStudentsIntoBalancedChunks($finalStudentPrograms, $studentsPerClass)
            ->values()
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
                            'studentProgramId' => (int) $student->student_program_id,
                            'studentId' => (int) $student->student_id,
                            'applicationTrackingNumber' => $student->application_tracking_number,
                            'name' => trim($firstName.' '.$middleName.' '.$lastName),
                        ];
                    })->values()->all(),
                ];
            })
            ->all();
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
            ->leftJoin('academic_calendar_student_programs', function ($join): void {
                $join->on('academic_calendar_student_programs.academic_calendar_class_id', '=', 'academic_calandar_classes.id')
                    ->whereNull('academic_calendar_student_programs.deleted_at');
            })
            ->leftJoin('student_programs', 'student_programs.id', '=', 'academic_calendar_student_programs.student_program_id')
            ->leftJoin('students', 'students.id', '=', 'student_programs.student_id')
            ->leftJoin('genders', 'genders.id', '=', 'students.gender_id')
            ->where('academic_calandar_classes.class_config_id', $classConfig->id)
            ->whereNull('academic_calandar_classes.deleted_at')
            ->groupBy('academic_calandar_classes.id', 'academic_calandar_classes.name')
            ->select([
                'academic_calandar_classes.id',
                'academic_calandar_classes.name',
                DB::raw('COUNT(academic_calendar_student_programs.id) as student_count'),
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

    private function resolveAssignedStudentProgramIds(?ClassConfig $classConfig): Collection
    {
        if (! $classConfig instanceof ClassConfig) {
            return collect();
        }

        return AcademicCalendarStudentProgram::query()
            ->join('academic_calandar_classes', 'academic_calandar_classes.id', '=', 'academic_calendar_student_programs.academic_calendar_class_id')
            ->where('academic_calandar_classes.class_config_id', $classConfig->id)
            ->pluck('academic_calendar_student_programs.student_program_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values();
    }

    private function filterUnassignedFinalStudentPrograms(Collection $finalStudentPrograms, Collection $assignedStudentProgramIds): Collection
    {
        if ($assignedStudentProgramIds->isEmpty()) {
            return $finalStudentPrograms->values();
        }

        $assignedLookup = $assignedStudentProgramIds
            ->mapWithKeys(fn (int $id): array => [$id => true])
            ->all();

        return $finalStudentPrograms
            ->reject(fn (mixed $student): bool => isset($assignedLookup[(int) $student->student_program_id]))
            ->values();
    }

    private function resolveExistingClassesForAllocation(?ClassConfig $classConfig): Collection
    {
        if (! $classConfig instanceof ClassConfig) {
            return collect();
        }

        return AcademicCalendarClass::query()
            ->leftJoin('academic_calendar_student_programs', function ($join): void {
                $join->on('academic_calendar_student_programs.academic_calendar_class_id', '=', 'academic_calandar_classes.id')
                    ->whereNull('academic_calendar_student_programs.deleted_at');
            })
            ->where('academic_calandar_classes.class_config_id', $classConfig->id)
            ->whereNull('academic_calandar_classes.deleted_at')
            ->groupBy('academic_calandar_classes.id', 'academic_calandar_classes.name')
            ->select([
                'academic_calandar_classes.id',
                'academic_calandar_classes.name',
                DB::raw('COUNT(academic_calendar_student_programs.id) as student_count'),
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
