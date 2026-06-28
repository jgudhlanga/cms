<?php

namespace App\Http\Controllers\AcademicCalendars;

use App\Enums\Shared\GenderEnum;
use App\Exports\AcademicCalendars\CourseWorkImportTemplateExport;
use App\Exports\AcademicCalendars\CourseWorkMarksheetExport;
use App\Http\Controllers\Concerns\ResolvesAcademicCalendarFromCalendarYear;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicCalendars\AcademicCalendarRequest;
use App\Http\Requests\AcademicCalendars\ClassConfigRequest;
use App\Http\Requests\AcademicCalendars\CourseWorkImportPreviewRequest;
use App\Http\Requests\AcademicCalendars\CourseWorkImportProcessRequest;
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
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\ModeOfStudy;
use App\Models\Students\StudentEnrolment;
use App\Queries\Enrolments\ConfirmedStudentsQuery;
use App\Services\AcademicCalendars\ClassListDataService;
use App\Services\AcademicCalendars\ClassListPdfService;
use App\Services\AcademicCalendars\CourseWorkImportService;
use App\Services\AcademicCalendars\CourseWorkImportTemplateService;
use App\Services\AcademicCalendars\CourseWorkMarksheetDataService;
use App\Services\AcademicCalendars\CourseWorkMarksheetPdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
        $finalStudentApplications = $this->resolveFinalStudentApplications(
            $institutionDepartment,
            $calendarIdsForYear,
            (int) $departmentLevelId,
            (int) $departmentCourseId,
            (int) $modeOfStudyId
        );

        $assignedStudentEnrolmentIds = $this->resolveAssignedStudentEnrolmentIds($classConfig);
        $unassignedFinalStudentApplications = $this->filterUnassignedFinalStudentApplications($finalStudentApplications, $assignedStudentEnrolmentIds);
        $existingClasses = $this->resolveExistingClassesForAllocation($classConfig);
        $classNamePrefix = $this->resolveClassNamePrefix($level);
        $classNumberOffset = $this->resolveClassNumberOffset($existingClasses->pluck('name'), $classNamePrefix, $mode, $classConfig);
        $previewClasses = $this->buildPreviewClasses(
            $unassignedFinalStudentApplications,
            (int) ($classConfig?->students_per_class ?? 0),
            $classNamePrefix,
            [],
            $mode,
            $classNumberOffset,
            $classConfig
        );
        $previewClasses = [
            ...$this->resolveExistingClassPreviews($classConfig),
            ...$previewClasses,
        ];
        $populatedExistingClassCount = $existingClasses->filter(
            fn ($row): bool => (int) ($row->student_count ?? 0) > 0
        )->count();

        $context = $this->buildGenerationContext(
            $institutionDepartment,
            $academicCalendar,
            $course,
            $level,
            $mode,
            $classConfig,
            $finalStudentApplications,
            $unassignedFinalStudentApplications,
            $populatedExistingClassCount > 0,
            $populatedExistingClassCount
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
            'canViewCourseWork' => auth()->user()?->can('viewAny', CourseWorkMark::class) ?? false,
            'canExportClassList' => auth()->user()?->can('export', AcademicCalendar::class) ?? false,
            'canAssignClassLecturer' => auth()->user()?->can('update:academic-calendars') ?? false,
        ]);
    }

    public function exportDepartmentAcademicCalendarClassList(
        Request $request,
        InstitutionDepartment $institutionDepartment,
        string $calendar_year,
        ClassListDataService $classListDataService,
        ClassListPdfService $classListPdfService,
    ): \Illuminate\Http\Response {
        $this->authorize('export', AcademicCalendar::class);

        $academicCalendar = $this->academicCalendarFromCalendarYear($calendar_year);
        $classConfig = $this->resolveClassConfigForDepartment($institutionDepartment, $academicCalendar, $request);

        $classIds = collect($request->query('class_ids', []))
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values()
            ->all();

        $data = $classListDataService->assembleForClassConfig($classConfig, $classIds);
        $viewData = $classListPdfService->assembleViewData($data);
        $fileName = sprintf('class-list-%s-%s.pdf', $classConfig->id, time());

        return Pdf::loadView('academic-calendars.class-list', $viewData)->stream($fileName);
    }

    public function exportDepartmentAcademicCalendarClassListForClass(
        Request $request,
        InstitutionDepartment $institutionDepartment,
        string $calendar_year,
        AcademicCalendarClass $academicCalendarClass,
        ClassListDataService $classListDataService,
        ClassListPdfService $classListPdfService,
    ): \Illuminate\Http\Response {
        $this->authorize('export', AcademicCalendar::class);

        $academicCalendar = $this->academicCalendarFromCalendarYear($calendar_year);

        $academicCalendarClass->loadMissing('classConfig');
        $classConfig = $academicCalendarClass->classConfig;

        abort_unless(
            $classConfig instanceof ClassConfig
            && (int) $classConfig->institution_department_id === (int) $institutionDepartment->id
            && (string) $classConfig->calendar_year === (string) $academicCalendar->calendar_year,
            404
        );

        $data = $classListDataService->assembleForClassConfig($classConfig, [(int) $academicCalendarClass->id]);
        $viewData = $classListPdfService->assembleViewData($data);
        $fileName = sprintf('class-list-%s-%s.pdf', $academicCalendarClass->id, time());

        return Pdf::loadView('academic-calendars.class-list', $viewData)->stream($fileName);
    }

    public function showDepartmentAcademicCalendarClassConfigCourseWorkMarksheet(
        Request $request,
        InstitutionDepartment $institutionDepartment,
        string $calendar_year,
    ): Response {
        $this->authorize('viewAny', CourseWorkMark::class);

        $academicCalendar = $this->academicCalendarFromCalendarYear($calendar_year);
        $classConfig = $this->resolveClassConfigForDepartment($institutionDepartment, $academicCalendar, $request);

        $course = $classConfig->departmentCourse ?? DepartmentCourse::query()->find($classConfig->department_course_id);
        $level = $classConfig->departmentLevel ?? DepartmentLevel::query()->find($classConfig->department_level_id);
        $mode = $classConfig->modeOfStudy ?? ModeOfStudy::query()->find($classConfig->mode_of_study_id);

        return Inertia::render('institution/academicCalendars/DepartmentAcademicCalendarClassConfigCourseWorkMarksheet', [
            'department' => InstitutionDepartmentResource::make($institutionDepartment),
            'academicCalendar' => AcademicCalendarResource::make($academicCalendar),
            'course' => DepartmentCourseResource::make($course),
            'level' => DepartmentLevelResource::make($level),
            'mode' => ModeOfStudyResource::make($mode),
            'classConfig' => ClassConfigResource::make($classConfig),
            'classConfigQuery' => $this->classConfigQueryParams($classConfig, $request),
            'canCreateCourseWork' => auth()->user()?->can('create', CourseWorkMark::class) ?? false,
            'canUpdateCourseWork' => auth()->user()?->can('update', CourseWorkMark::class) ?? false,
            'canExportCourseWork' => auth()->user()?->can('export', CourseWorkMark::class) ?? false,
            'canImportCourseWork' => auth()->user()?->can('import', CourseWorkMark::class) ?? false,
        ]);
    }

    public function showDepartmentAcademicCalendarClassConfigCourseWorkImport(
        Request $request,
        InstitutionDepartment $institutionDepartment,
        string $calendar_year,
    ): Response {
        $this->authorize('import', CourseWorkMark::class);

        $academicCalendar = $this->academicCalendarFromCalendarYear($calendar_year);
        $classConfig = $this->resolveClassConfigForDepartment($institutionDepartment, $academicCalendar, $request);

        $course = $classConfig->departmentCourse ?? DepartmentCourse::query()->find($classConfig->department_course_id);
        $level = $classConfig->departmentLevel ?? DepartmentLevel::query()->find($classConfig->department_level_id);
        $mode = $classConfig->modeOfStudy ?? ModeOfStudy::query()->find($classConfig->mode_of_study_id);

        return Inertia::render('institution/academicCalendars/DepartmentAcademicCalendarClassConfigCourseWorkImport', [
            'department' => InstitutionDepartmentResource::make($institutionDepartment),
            'academicCalendar' => AcademicCalendarResource::make($academicCalendar),
            'course' => DepartmentCourseResource::make($course),
            'level' => DepartmentLevelResource::make($level),
            'mode' => ModeOfStudyResource::make($mode),
            'classConfig' => ClassConfigResource::make($classConfig),
            'classConfigQuery' => $this->classConfigQueryParams($classConfig, $request),
            'canImportCourseWork' => auth()->user()?->can('import', CourseWorkMark::class) ?? false,
            'courseWorkImportResult' => session('courseWorkImportResult'),
        ]);
    }

    public function downloadDepartmentAcademicCalendarClassConfigCourseWorkImportTemplate(
        Request $request,
        InstitutionDepartment $institutionDepartment,
        string $calendar_year,
        CourseWorkImportTemplateService $templateService,
    ): BinaryFileResponse {
        $this->authorize('import', CourseWorkMark::class);

        $academicCalendar = $this->academicCalendarFromCalendarYear($calendar_year);
        $classConfig = $this->resolveClassConfigForDepartment($institutionDepartment, $academicCalendar, $request);

        $moduleId = (int) $request->query('module', 0);
        abort_if($moduleId < 1, 422, __('academic_calendar.course_work_module_required'));

        $data = $templateService->assembleForClassConfig((int) $classConfig->id, $moduleId);
        $fileName = $templateService->downloadFileName($data);

        return Excel::download(new CourseWorkImportTemplateExport($data), $fileName);
    }

    public function previewDepartmentAcademicCalendarClassConfigCourseWorkImport(
        CourseWorkImportPreviewRequest $request,
        InstitutionDepartment $institutionDepartment,
        string $calendar_year,
        CourseWorkImportService $importService,
    ): JsonResponse {
        $this->authorize('import', CourseWorkMark::class);

        $academicCalendar = $this->academicCalendarFromCalendarYear($calendar_year);
        $classConfig = $this->resolveClassConfigForDepartment($institutionDepartment, $academicCalendar, $request);

        $moduleId = (int) $request->validated('module');
        $file = $request->file('file');

        abort_if($file === null, 422);

        $preview = $importService->preview((int) $classConfig->id, $moduleId, $file);

        return response()->json($preview);
    }

    public function processDepartmentAcademicCalendarClassConfigCourseWorkImport(
        CourseWorkImportProcessRequest $request,
        InstitutionDepartment $institutionDepartment,
        string $calendar_year,
        CourseWorkImportService $importService,
    ): RedirectResponse {
        $this->authorize('import', CourseWorkMark::class);

        $academicCalendar = $this->academicCalendarFromCalendarYear($calendar_year);
        $classConfig = $this->resolveClassConfigForDepartment($institutionDepartment, $academicCalendar, $request);

        $moduleId = (int) $request->validated('module');
        $previewToken = (string) $request->validated('preview_token');

        $result = $importService->processFromPreview((int) $classConfig->id, $moduleId, $previewToken);

        return redirect()
            ->route('academic-calendars.department-classes.course-work-import', [
                'institution_department' => $institutionDepartment->id,
                'calendar_year' => $calendar_year,
                ...$this->classConfigQueryParams($classConfig, $request),
            ])
            ->with('courseWorkImportResult', $result)
            ->with(
                'success',
                __('academic_calendar.course_work_import_success', [
                    'succeeded' => $result['rowsSucceeded'],
                    'failed' => $result['rowsFailed'],
                    'skipped' => $result['rowsSkipped'],
                ]),
            );
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
            'lecturerMetaData.staff.user',
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
            'canViewCourseWork' => auth()->user()?->can('viewAny', CourseWorkMark::class) ?? false,
            'canExportClassList' => auth()->user()?->can('export', AcademicCalendar::class) ?? false,
            'canAssignClassLecturer' => auth()->user()?->can('update', $academicCalendar) ?? false,
            'moveTargetClasses' => $moveTargetClasses,
            'siblingAcademicCalendarClasses' => $siblingAcademicCalendarClasses,
            'academicCalendarClass' => [
                'id' => $academicCalendarClass->id,
                'name' => $academicCalendarClass->name,
                'description' => $academicCalendarClass->description,
                'studentCount' => count($students),
                'students' => $students,
                'lecturer' => $this->lecturerPayload($academicCalendarClass->lecturerMetaData),
            ],
        ]);
    }

    public function showDepartmentAcademicCalendarClassStudentCourseWork(
        InstitutionDepartment $institutionDepartment,
        string $calendar_year,
        AcademicCalendarClass $academicCalendarClass,
        StudentEnrolment $studentEnrolment,
    ): Response {
        $this->authorize('viewAny', CourseWorkMark::class);

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

        $enrolmentInClass = AcademicCalendarStudentEnrolment::query()
            ->where('academic_calendar_class_id', $academicCalendarClass->id)
            ->where('student_enrolment_id', $studentEnrolment->id)
            ->whereNull('deleted_at')
            ->exists();

        abort_unless($enrolmentInClass, 404);

        $students = $this->studentsPayloadForAcademicCalendarClass($academicCalendarClass);
        $student = collect($students)->firstWhere('studentEnrolmentId', (int) $studentEnrolment->id);

        abort_unless($student !== null, 404);

        $course = $classConfig->departmentCourse ?? DepartmentCourse::query()->find($classConfig->department_course_id);
        $level = $classConfig->departmentLevel ?? DepartmentLevel::query()->find($classConfig->department_level_id);
        $mode = $classConfig->modeOfStudy ?? ModeOfStudy::query()->find($classConfig->mode_of_study_id);

        return Inertia::render('institution/academicCalendars/DepartmentAcademicCalendarClassStudentCourseWork', [
            'department' => InstitutionDepartmentResource::make($institutionDepartment),
            'academicCalendar' => AcademicCalendarResource::make($academicCalendar),
            'course' => DepartmentCourseResource::make($course),
            'level' => DepartmentLevelResource::make($level),
            'mode' => ModeOfStudyResource::make($mode),
            'classConfig' => ClassConfigResource::make($classConfig),
            'academicCalendarClass' => [
                'id' => $academicCalendarClass->id,
                'name' => $academicCalendarClass->name,
            ],
            'student' => $student,
            'canCreateCourseWork' => auth()->user()?->can('create', CourseWorkMark::class) ?? false,
            'canUpdateCourseWork' => auth()->user()?->can('update', CourseWorkMark::class) ?? false,
            'canViewCourseWorkAuditTrail' => auth()->user()?->can('viewAuditTrail', CourseWorkMark::class) ?? false,
        ]);
    }

    public function exportDepartmentAcademicCalendarClassConfigCourseWork(
        Request $request,
        InstitutionDepartment $institutionDepartment,
        string $calendar_year,
        CourseWorkMarksheetDataService $marksheetDataService,
        CourseWorkMarksheetPdfService $marksheetPdfService,
    ): BinaryFileResponse|\Illuminate\Http\Response {
        $this->authorize('export', CourseWorkMark::class);

        $academicCalendar = $this->academicCalendarFromCalendarYear($calendar_year);
        $classConfig = $this->resolveClassConfigForDepartment($institutionDepartment, $academicCalendar, $request);

        $moduleId = (int) $request->query('module', 0);
        abort_if($moduleId < 1, 422, __('academic_calendar.course_work_module_required'));

        $data = $marksheetDataService->assembleForClassConfig((int) $classConfig->id, $moduleId);
        $marksheetDataService->assertExportable($data['issues'], $request->boolean('strict'));

        $subjectCode = Str::slug((string) ($data['header']['subjectCode'] ?? 'marksheet'));
        $format = strtolower((string) $request->query('format', 'xlsx'));

        if ($format === 'pdf') {
            $fileName = sprintf('%s-course-work-marksheet-%s.pdf', $subjectCode, time());
            $viewData = $marksheetPdfService->assembleViewData($data);

            return Pdf::loadView('academic-calendars.course-work-marksheet', $viewData)->stream($fileName);
        }

        $fileName = sprintf('%s-course-work-marksheet-%s.xlsx', $subjectCode, time());

        return Excel::download(new CourseWorkMarksheetExport($data), $fileName);
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

        ClassConfig::query()->updateOrCreate(
            [
                'calendar_year' => (string) $academicCalendar->calendar_year,
                'institution_department_id' => $institutionDepartment->id,
                'department_course_id' => (int) $validated['department_course_id'],
                'department_level_id' => (int) $validated['department_level_id'],
                'mode_of_study_id' => (int) $validated['mode_of_study_id'],
                'academic_year_option_id' => (int) $validated['academic_year_option_id'],
            ],
            [
                'students_per_class' => (int) $validated['students_per_class'],
                'course_syllabus_ids' => $validated['course_syllabus_ids'] ?? [],
            ],
        );

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
        $finalStudentApplications = $this->resolveFinalStudentApplications(
            $institutionDepartment,
            $calendarIdsForYear,
            (int) $validated['department_level_id'],
            (int) $validated['department_course_id'],
            (int) $validated['mode_of_study_id']
        );
        $assignedStudentEnrolmentIds = $this->resolveAssignedStudentEnrolmentIds($classConfig);
        $unassignedFinalStudentApplications = $this->filterUnassignedFinalStudentApplications($finalStudentApplications, $assignedStudentEnrolmentIds);
        $level = DepartmentLevel::find((int) $validated['department_level_id']);
        $mode = ModeOfStudy::query()->find((int) $validated['mode_of_study_id']);
        $existingClasses = $this->resolveExistingClassesForAllocation($classConfig);
        $tenantId = function_exists('tenant') ? tenant('id') : null;
        $tenantId = $tenantId ?? auth()->user()?->tenant_id;

        DB::transaction(function () use ($classConfig, $unassignedFinalStudentApplications, $existingClasses, $validated, $level, $mode, $tenantId): void {
            $remainingStudents = $unassignedFinalStudentApplications->values();

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

                $remainingStudents = $this->filterUnassignedFinalStudentApplications(
                    $remainingStudents,
                    $balancedChunk->pluck('student_enrolment_id')->map(fn (mixed $id): int => (int) $id)
                )->values();
            }

            if ($remainingStudents->isEmpty()) {
                return;
            }

            $classNamePrefix = $this->resolveClassNamePrefix($level);
            $classNumberOffset = $this->resolveClassNumberOffset($existingClasses->pluck('name'), $classNamePrefix, $mode, $classConfig);
            $newPreviewClasses = $this->buildPreviewClasses(
                $remainingStudents,
                (int) $validated['students_per_class'],
                $classNamePrefix,
                [],
                $mode,
                $classNumberOffset,
                $classConfig
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
            ->join('student_applications', 'student_applications.id', '=', 'student_enrolments.student_application_id')
            ->join('students', 'students.id', '=', 'student_applications.student_id')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->leftJoin('genders', 'genders.id', '=', 'students.gender_id')
            ->where('academic_calendar_student_enrolments.academic_calendar_class_id', $academicCalendarClass->id)
            ->whereNull('academic_calendar_student_enrolments.deleted_at')
            ->select([
                'student_enrolments.id as student_enrolment_id',
                'student_applications.application_tracking_number',
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
    private function resolveFinalStudentApplications(
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
        Collection $finalStudentApplications,
        int $studentsPerClass,
        string $classNamePrefix = 'Class',
        array $existingClassMap = [],
        ?ModeOfStudy $mode = null,
        int $classNumberOffset = 0,
        ?ClassConfig $classConfig = null,
    ): array {
        if ($studentsPerClass < 1 || $finalStudentApplications->isEmpty()) {
            return [];
        }

        $modeName = $mode instanceof ModeOfStudy ? trim((string) ($mode->name ?? '')) : '';
        $nameBase = $modeName !== ''
            ? $classNamePrefix.' - '.$modeName
            : $classNamePrefix;

        $chunks = $this->mergeTrailingChunkIfBelowHalf(
            $this->splitStudentsIntoBalancedChunks($finalStudentApplications, $studentsPerClass)->values(),
            $studentsPerClass
        );

        return $chunks
            ->map(function (Collection $chunk, int $index) use ($nameBase, $existingClassMap, $classNumberOffset, $classConfig): array {
                $genderCounts = [
                    'male' => 0,
                    'female' => 0,
                    'unknown' => 0,
                ];
                $className = $nameBase.' - '.($index + 1 + $classNumberOffset);

                if ($classConfig instanceof ClassConfig) {
                    $className .= ' - '.$classConfig->id;
                }

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
            ->filter(fn ($student) => $this->normalizeGenderValue($student->gender_title ?? null) === GenderEnum::MALE->value
            )
            ->values();

        $femaleStudents = $students
            ->filter(fn ($student) => $this->normalizeGenderValue($student->gender_title ?? null) === GenderEnum::FEMALE->value
            )
            ->values();

        $unknownGenderStudents = $students
            ->filter(fn ($student) => $this->normalizeGenderValue($student->gender_title ?? null) === null
            )
            ->values();

        $totalStudents = $students->count();
        $totalClasses = (int) ceil($totalStudents / $studentsPerClass);

        $classChunks = collect();

        for ($classIndex = 0; $classIndex < $totalClasses; $classIndex++) {

            $remainingClasses = $totalClasses - $classIndex;

            $remainingMale = $maleStudents->count();
            $remainingFemale = $femaleStudents->count();
            $remainingUnknown = $unknownGenderStudents->count();

            $remainingStudents = $remainingMale + $remainingFemale + $remainingUnknown;

            $capacity = min($studentsPerClass, $remainingStudents);

            /*
            |--------------------------------------------------------------------------
            | Calculate fair proportional distribution
            |--------------------------------------------------------------------------
            */

            $maleTarget = (int) round($remainingMale / $remainingClasses);
            $femaleTarget = (int) round($remainingFemale / $remainingClasses);

            /*
            |--------------------------------------------------------------------------
            | Ensure we do not exceed class capacity
            |--------------------------------------------------------------------------
            */

            $allocated = $maleTarget + $femaleTarget;

            if ($allocated > $capacity) {

                $overflow = $allocated - $capacity;

                if ($maleTarget >= $femaleTarget) {
                    $maleTarget -= $overflow;
                } else {
                    $femaleTarget -= $overflow;
                }
            }

            $classStudents = collect()
                ->merge($maleStudents->splice(0, min($maleTarget, $remainingMale)))
                ->merge($femaleStudents->splice(0, min($femaleTarget, $remainingFemale)));

            /*
            |--------------------------------------------------------------------------
            | Fill remaining seats fairly
            |--------------------------------------------------------------------------
            */

            $missingSeats = $capacity - $classStudents->count();

            if ($missingSeats > 0 && $maleStudents->isNotEmpty()) {

                $take = min($missingSeats, $maleStudents->count());

                $classStudents = $classStudents->merge(
                    $maleStudents->splice(0, $take)
                );

                $missingSeats -= $take;
            }

            if ($missingSeats > 0 && $femaleStudents->isNotEmpty()) {

                $take = min($missingSeats, $femaleStudents->count());

                $classStudents = $classStudents->merge(
                    $femaleStudents->splice(0, $take)
                );

                $missingSeats -= $take;
            }

            if ($missingSeats > 0 && $unknownGenderStudents->isNotEmpty()) {

                $classStudents = $classStudents->merge(
                    $unknownGenderStudents->splice(0, $missingSeats)
                );
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
                $join->on('academic_calendar_student_enrolments.academic_calendar_class_id', '=', 'academic_calendar_classes.id')
                    ->whereNull('academic_calendar_student_enrolments.deleted_at');
            })
            ->leftJoin('student_enrolments', 'student_enrolments.id', '=', 'academic_calendar_student_enrolments.student_enrolment_id')
            ->leftJoin('students', 'students.id', '=', 'student_enrolments.student_id')
            ->leftJoin('genders', 'genders.id', '=', 'students.gender_id')
            ->where('academic_calendar_classes.class_config_id', $classConfig->id)
            ->whereNull('academic_calendar_classes.deleted_at')
            ->groupBy('academic_calendar_classes.id', 'academic_calendar_classes.name')
            ->select([
                'academic_calendar_classes.id',
                'academic_calendar_classes.name',
                DB::raw('COUNT(academic_calendar_student_enrolments.id) as student_count'),
                DB::raw("SUM(CASE WHEN LOWER(genders.title) LIKE 'male%' THEN 1 ELSE 0 END) as male_count"),
                DB::raw("SUM(CASE WHEN LOWER(genders.title) LIKE 'female%' THEN 1 ELSE 0 END) as female_count"),
            ])
            ->orderBy('academic_calendar_classes.id')
            ->get()
            ->filter(fn ($class): bool => (int) ($class->student_count ?? 0) > 0)
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

        return $this->attachLecturersToClassPreviews($previews);
    }

    /**
     * @param  list<array<string, mixed>>  $previews
     * @return list<array<string, mixed>>
     */
    private function attachLecturersToClassPreviews(array $previews): array
    {
        $classIds = collect($previews)
            ->pluck('academicCalendarClassId')
            ->filter(fn (mixed $id): bool => $id !== null && (int) $id > 0)
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();

        if ($classIds === []) {
            return array_map(function (array $preview): array {
                $preview['lecturer'] = null;

                return $preview;
            }, $previews);
        }

        $lecturersByClassId = AcademicCalendarClass::query()
            ->with(['lecturerMetaData.staff.user'])
            ->whereIn('id', $classIds)
            ->get()
            ->mapWithKeys(fn (AcademicCalendarClass $class): array => [
                (int) $class->id => $this->lecturerPayload($class->lecturerMetaData),
            ]);

        return array_map(function (array $preview) use ($lecturersByClassId): array {
            $classId = (int) ($preview['academicCalendarClassId'] ?? 0);
            $preview['lecturer'] = $classId > 0 ? ($lecturersByClassId[$classId] ?? null) : null;

            return $preview;
        }, $previews);
    }

    /**
     * @return array{id: int, name: string}|null
     */
    private function lecturerPayload(?\App\Models\AcademicCalendars\AcademicCalendarClassMetaData $lecturerMetaData): ?array
    {
        if ($lecturerMetaData === null || $lecturerMetaData->staff_id === null) {
            return null;
        }

        $lecturerMetaData->loadMissing('staff.user');
        $user = $lecturerMetaData->staff?->user;

        if ($user === null) {
            return null;
        }

        return [
            'id' => (int) $lecturerMetaData->staff_id,
            'name' => trim(sprintf('%s %s', (string) ($user->first_name ?? ''), (string) ($user->last_name ?? ''))),
        ];
    }

    private function buildGenerationContext(
        InstitutionDepartment $institutionDepartment,
        AcademicCalendar $academicCalendar,
        ?DepartmentCourse $course,
        ?DepartmentLevel $level,
        ?ModeOfStudy $mode,
        ?ClassConfig $classConfig,
        Collection $finalStudentApplications,
        Collection $unassignedFinalStudentApplications,
        bool $hasExistingClasses,
        int $populatedExistingClassCount,
    ): array {
        $newStudentGenderCounts = [
            'male' => 0,
            'female' => 0,
            'unknown' => 0,
        ];

        foreach ($unassignedFinalStudentApplications as $student) {
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
            'finalStudentCount' => $finalStudentApplications->count(),
            'newFinalStudentCount' => $unassignedFinalStudentApplications->count(),
            'newStudentGenderCounts' => $newStudentGenderCounts,
            'hasExistingClasses' => $hasExistingClasses,
            'populatedExistingClassCount' => $populatedExistingClassCount,
        ];
    }

    private function resolveAssignedStudentEnrolmentIds(?ClassConfig $classConfig): Collection
    {
        if (! $classConfig instanceof ClassConfig) {
            return collect();
        }

        return AcademicCalendarStudentEnrolment::query()
            ->join('academic_calendar_classes', 'academic_calendar_classes.id', '=', 'academic_calendar_student_enrolments.academic_calendar_class_id')
            ->where('academic_calendar_classes.class_config_id', $classConfig->id)
            ->pluck('academic_calendar_student_enrolments.student_enrolment_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values();
    }

    private function filterUnassignedFinalStudentApplications(Collection $finalStudentApplications, Collection $assignedStudentEnrolmentIds): Collection
    {
        if ($assignedStudentEnrolmentIds->isEmpty()) {
            return $finalStudentApplications->values();
        }

        $assignedLookup = $assignedStudentEnrolmentIds
            ->mapWithKeys(fn (int $id): array => [$id => true])
            ->all();

        return $finalStudentApplications
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
                $join->on('academic_calendar_student_enrolments.academic_calendar_class_id', '=', 'academic_calendar_classes.id')
                    ->whereNull('academic_calendar_student_enrolments.deleted_at');
            })
            ->where('academic_calendar_classes.class_config_id', $classConfig->id)
            ->whereNull('academic_calendar_classes.deleted_at')
            ->groupBy('academic_calendar_classes.id', 'academic_calendar_classes.name')
            ->select([
                'academic_calendar_classes.id',
                'academic_calendar_classes.name',
                DB::raw('COUNT(academic_calendar_student_enrolments.id) as student_count'),
            ])
            ->orderBy('academic_calendar_classes.id')
            ->get();
    }

    private function resolveClassNumberOffset(
        Collection $existingClassNames,
        string $classNamePrefix = 'Class',
        ?ModeOfStudy $mode = null,
        ?ClassConfig $classConfig = null,
    ): int {
        if ($existingClassNames->isEmpty()) {
            return 0;
        }

        $modeName = $mode instanceof ModeOfStudy ? trim((string) ($mode->name ?? '')) : '';
        $nameBase = $modeName !== ''
            ? $classNamePrefix.' - '.$modeName
            : $classNamePrefix;
        $configSuffix = $classConfig instanceof ClassConfig
            ? '\s-\s'.preg_quote((string) $classConfig->id, '/')
            : '';
        $pattern = '/^'.preg_quote($nameBase, '/').'\s-\s(\d+)'.$configSuffix.'$/';
        $legacyPattern = $classConfig instanceof ClassConfig
            ? '/^'.preg_quote($nameBase, '/').'\s-\s(\d+)$/'
            : null;
        $highestClassNumber = 0;

        foreach ($existingClassNames as $existingClassName) {
            $className = (string) $existingClassName;

            if (preg_match($pattern, $className, $matches)) {
                $highestClassNumber = max($highestClassNumber, (int) ($matches[1] ?? 0));

                continue;
            }

            if ($legacyPattern !== null && preg_match($legacyPattern, $className, $matches)) {
                $highestClassNumber = max($highestClassNumber, (int) ($matches[1] ?? 0));
            }
        }

        return $highestClassNumber;
    }

    private function resolveClassConfigForDepartment(
        InstitutionDepartment $institutionDepartment,
        AcademicCalendar $academicCalendar,
        Request $request,
    ): ClassConfig {
        $classConfigId = (int) $request->query('class_config_id', 0);
        $departmentLevelId = (int) $request->query('department_level_id', 0);
        $departmentCourseId = (int) $request->query('department_course_id', 0);
        $modeOfStudyId = (int) $request->query('mode_of_study_id', 0);

        $classConfig = ClassConfig::query()
            ->when($classConfigId > 0, fn ($query) => $query->where('id', $classConfigId))
            ->when($classConfigId < 1 && $departmentLevelId > 0 && $departmentCourseId > 0 && $modeOfStudyId > 0, function ($query) use (
                $academicCalendar,
                $institutionDepartment,
                $departmentLevelId,
                $departmentCourseId,
                $modeOfStudyId,
            ): void {
                $query
                    ->where('calendar_year', $academicCalendar->calendar_year)
                    ->where('institution_department_id', $institutionDepartment->id)
                    ->where('department_level_id', $departmentLevelId)
                    ->where('department_course_id', $departmentCourseId)
                    ->where('mode_of_study_id', $modeOfStudyId)
                    ->whereNull('academic_year_option_id');
            })
            ->first();

        abort_unless(
            $classConfig instanceof ClassConfig
            && (int) $classConfig->institution_department_id === (int) $institutionDepartment->id
            && (string) $classConfig->calendar_year === (string) $academicCalendar->calendar_year,
            404,
        );

        return $classConfig;
    }

    /**
     * @return array<string, string>
     */
    private function classConfigQueryParams(ClassConfig $classConfig, Request $request): array
    {
        return array_filter([
            'class_config_id' => (string) $classConfig->id,
            'department_course_id' => $request->query('department_course_id') ? (string) $request->query('department_course_id') : (string) $classConfig->department_course_id,
            'department_level_id' => $request->query('department_level_id') ? (string) $request->query('department_level_id') : (string) $classConfig->department_level_id,
            'mode_of_study_id' => $request->query('mode_of_study_id') ? (string) $request->query('mode_of_study_id') : (string) $classConfig->mode_of_study_id,
        ]);
    }
}
