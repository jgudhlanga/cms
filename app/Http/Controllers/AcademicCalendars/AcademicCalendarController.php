<?php

namespace App\Http\Controllers\AcademicCalendars;

use App\Enums\AcademicCalendars\ClassMetaDataTypeEnum;
use App\Enums\Shared\ClassListTypeEnum;
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

        $previewClasses = $this->buildPreviewClasses(
            $finalStudentPrograms,
            (int) ($classConfig?->students_per_class ?? 0),
            $this->resolveClassNamePrefix($level),
            $this->resolveExistingClassMap($classConfig)
        );
        $context = $this->buildGenerationContext($institutionDepartment, $academicCalendar, $course, $level, $mode, $classConfig, $finalStudentPrograms);

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

        $academicCalendarClass->load('classConfig');

        abort_unless(
            (int) $academicCalendarClass->classConfig?->institution_department_id === (int) $institutionDepartment->id
            && (int) $academicCalendarClass->classConfig?->academic_calendar_id === (int) $academicCalendar->id,
            404
        );

        $students = AcademicCalendarStudentProgram::query()
            ->join('student_programs', 'student_programs.id', '=', 'academic_calendar_student_programs.student_program_id')
            ->join('students', 'students.id', '=', 'student_programs.student_id')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->where('academic_calendar_student_programs.academic_calendar_class_id', $academicCalendarClass->id)
            ->select([
                'student_programs.id as student_program_id',
                'student_programs.application_tracking_number',
                'users.first_name',
                'users.last_name',
            ])
            ->orderBy('users.first_name')
            ->orderBy('users.last_name')
            ->get()
            ->map(function (mixed $student): array {
                return [
                    'studentProgramId' => (int) $student->student_program_id,
                    'applicationTrackingNumber' => $student->application_tracking_number,
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
        $previewClasses = $this->buildPreviewClasses(
            $finalStudentPrograms,
            (int) $validated['students_per_class'],
            $this->resolveClassNamePrefix(DepartmentLevel::find((int) $validated['department_level_id']))
        );
        $tenantId = function_exists('tenant') ? tenant('id') : null;
        $tenantId = $tenantId ?? auth()->user()?->tenant_id;

        DB::transaction(function () use ($classConfig, $previewClasses, $tenantId): void {
            $existingClassIds = AcademicCalendarClass::query()
                ->where('class_config_id', $classConfig->id)
                ->pluck('id');

            if ($existingClassIds->isNotEmpty()) {
                AcademicCalendarStudentProgram::query()
                    ->whereIn('academic_calendar_class_id', $existingClassIds)
                    ->delete();

                AcademicCalendarClassMetaData::query()
                    ->where('metadatable_type', AcademicCalendarClass::class)
                    ->whereIn('metadatable_id', $existingClassIds)
                    ->delete();

                AcademicCalendarClass::query()
                    ->whereIn('id', $existingClassIds)
                    ->delete();
            }

            foreach ($previewClasses as $previewClass) {
                $academicClass = AcademicCalendarClass::query()->create([
                    'tenant_id' => $tenantId,
                    'class_config_id' => $classConfig->id,
                    'name' => $previewClass['name'],
                    'description' => "Auto generated from final class list ({$previewClass['studentCount']} students)",
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
                'users.first_name',
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
        array $existingClassMap = []
    ): array {
        if ($studentsPerClass < 1 || $finalStudentPrograms->isEmpty()) {
            return [];
        }

        return $finalStudentPrograms
            ->chunk($studentsPerClass)
            ->values()
            ->map(function (Collection $chunk, int $index) use ($classNamePrefix, $existingClassMap): array {
                return [
                    'academicCalendarClassId' => $existingClassMap[$classNamePrefix.' '.($index + 1)] ?? null,
                    'name' => $classNamePrefix.' '.($index + 1),
                    'studentCount' => $chunk->count(),
                    'students' => $chunk->map(function (mixed $student): array {
                        $firstName = (string) ($student->first_name ?? '');
                        $lastName = (string) ($student->last_name ?? '');

                        return [
                            'studentProgramId' => (int) $student->student_program_id,
                            'studentId' => (int) $student->student_id,
                            'applicationTrackingNumber' => $student->application_tracking_number,
                            'name' => trim($firstName.' '.$lastName),
                        ];
                    })->values()->all(),
                ];
            })
            ->all();
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

    private function buildGenerationContext(
        InstitutionDepartment $institutionDepartment,
        AcademicCalendar $academicCalendar,
        ?DepartmentCourse $course,
        ?DepartmentLevel $level,
        ?ModeOfStudy $mode,
        ?ClassConfig $classConfig,
        Collection $finalStudentPrograms
    ): array {
        return [
            'institutionDepartmentId' => $institutionDepartment->id,
            'academicCalendarId' => $academicCalendar->id,
            'departmentLevelId' => $level?->id,
            'departmentCourseId' => $course?->id,
            'modeOfStudyId' => $mode?->id,
            'classConfigId' => $classConfig?->id,
            'studentsPerClass' => $classConfig?->students_per_class,
            'finalStudentCount' => $finalStudentPrograms->count(),
        ];
    }
}
