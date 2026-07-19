<?php

namespace App\Http\Controllers\Teaching;

use App\Exports\AcademicCalendars\CourseWorkImportTemplateExport;
use App\Exports\AcademicCalendars\CourseWorkMarksheetExport;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicCalendars\CourseWorkImportPreviewRequest;
use App\Http\Requests\AcademicCalendars\CourseWorkImportProcessRequest;
use App\Http\Resources\AcademicCalendars\AcademicCalendarResource;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Students\StudentEnrolment;
use App\Services\AcademicCalendars\ClassListDataService;
use App\Services\AcademicCalendars\ClassListPdfService;
use App\Services\AcademicCalendars\CourseWorkImportService;
use App\Services\AcademicCalendars\CourseWorkImportTemplateService;
use App\Services\AcademicCalendars\CourseWorkMarksheetDataService;
use App\Services\AcademicCalendars\CourseWorkMarksheetPdfService;
use App\Services\Lecturer\LecturerCourseWorkAccess;
use App\Services\Lecturer\LecturerTeachingListService;
use App\Support\AcademicCalendars\AcademicCalendarPeriodResolver;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ClassesController extends Controller
{
    public function __construct(
        private readonly LecturerTeachingListService $teachingListService,
        private readonly LecturerCourseWorkAccess $courseWorkAccess,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewLecturerClasses');

        $academicCalendar = Helper::resolveAcademicCalendar();

        return Inertia::render('teaching/classes/Index', [
            'classes' => $this->teachingListService->classesFor(auth()->user()),
            'academicCalendar' => AcademicCalendarResource::make($academicCalendar),
            'academicContextSubtitle' => $this->academicContextSubtitle($academicCalendar),
            'canEnterMarks' => $this->canEnterMarks(),
            'canExportCourseWork' => auth()->user()?->can('export', CourseWorkMark::class) ?? false,
            'canImportCourseWork' => auth()->user()?->can('import', CourseWorkMark::class) ?? false,
        ]);
    }

    public function show(AcademicCalendarClass $academicCalendarClass): Response
    {
        $this->authorize('viewLecturerClasses');

        $detail = $this->teachingListService->classDetailFor(auth()->user(), $academicCalendarClass);
        abort_if($detail === null, 403);

        $academicCalendar = Helper::resolveAcademicCalendar();

        return Inertia::render('teaching/classes/Show', [
            'classDetail' => $detail,
            'academicCalendar' => AcademicCalendarResource::make($academicCalendar),
            'academicContextSubtitle' => $this->academicContextSubtitle($academicCalendar),
            'canEnterMarks' => $this->canEnterMarks(),
            'canCreateCourseWork' => auth()->user()?->can('create', CourseWorkMark::class) ?? false,
            'canUpdateCourseWork' => auth()->user()?->can('update', CourseWorkMark::class) ?? false,
            'canExportCourseWork' => auth()->user()?->can('export', CourseWorkMark::class) ?? false,
            'canImportCourseWork' => auth()->user()?->can('import', CourseWorkMark::class) ?? false,
            'canExportClassList' => true,
        ]);
    }

    public function marksheet(
        AcademicCalendarClass $academicCalendarClass,
        CourseSyllabusModule $courseSyllabusModule,
    ): Response {
        $this->authorize('viewAny', CourseWorkMark::class);
        $this->courseWorkAccess->assertCanAccessClassModule(
            auth()->user(),
            (int) $academicCalendarClass->id,
            (int) $courseSyllabusModule->id,
        );

        $detail = $this->teachingListService->classDetailFor(auth()->user(), $academicCalendarClass);
        abort_if($detail === null, 403);

        $academicCalendar = Helper::resolveAcademicCalendar();
        $allowedModuleIds = $this->courseWorkAccess->allowedModuleIdsForClass(
            auth()->user(),
            (int) $academicCalendarClass->id,
        );

        return Inertia::render('teaching/classes/Marksheet', [
            'classDetail' => $detail,
            'module' => [
                'id' => (int) $courseSyllabusModule->id,
                'title' => (string) $courseSyllabusModule->title,
                'code' => (string) ($courseSyllabusModule->code ?? ''),
            ],
            'allowedModuleIds' => $allowedModuleIds,
            'academicCalendar' => AcademicCalendarResource::make($academicCalendar),
            'academicContextSubtitle' => $this->academicContextSubtitle($academicCalendar),
            'canCreateCourseWork' => auth()->user()?->can('create', CourseWorkMark::class) ?? false,
            'canUpdateCourseWork' => auth()->user()?->can('update', CourseWorkMark::class) ?? false,
            'canExportCourseWork' => auth()->user()?->can('export', CourseWorkMark::class) ?? false,
            'canImportCourseWork' => auth()->user()?->can('import', CourseWorkMark::class) ?? false,
        ]);
    }

    public function exportMarksheet(
        Request $request,
        AcademicCalendarClass $academicCalendarClass,
        CourseSyllabusModule $courseSyllabusModule,
        CourseWorkMarksheetDataService $marksheetDataService,
        CourseWorkMarksheetPdfService $marksheetPdfService,
    ): BinaryFileResponse|\Illuminate\Http\Response {
        $this->authorize('export', CourseWorkMark::class);
        $this->courseWorkAccess->assertCanAccessClassModule(
            auth()->user(),
            (int) $academicCalendarClass->id,
            (int) $courseSyllabusModule->id,
        );

        $data = $marksheetDataService->assemble(
            (int) $academicCalendarClass->id,
            (int) $courseSyllabusModule->id,
        );
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

    public function import(
        AcademicCalendarClass $academicCalendarClass,
        CourseSyllabusModule $courseSyllabusModule,
    ): Response {
        $this->authorize('import', CourseWorkMark::class);
        $this->courseWorkAccess->assertCanAccessClassModule(
            auth()->user(),
            (int) $academicCalendarClass->id,
            (int) $courseSyllabusModule->id,
        );

        $detail = $this->teachingListService->classDetailFor(auth()->user(), $academicCalendarClass);
        abort_if($detail === null, 403);

        $academicCalendar = Helper::resolveAcademicCalendar();

        return Inertia::render('teaching/classes/Import', [
            'classDetail' => $detail,
            'module' => [
                'id' => (int) $courseSyllabusModule->id,
                'title' => (string) $courseSyllabusModule->title,
                'code' => (string) ($courseSyllabusModule->code ?? ''),
            ],
            'academicCalendar' => AcademicCalendarResource::make($academicCalendar),
            'academicContextSubtitle' => $this->academicContextSubtitle($academicCalendar),
            'canImportCourseWork' => true,
            'courseWorkImportResult' => session('courseWorkImportResult'),
        ]);
    }

    public function importTemplate(
        AcademicCalendarClass $academicCalendarClass,
        CourseSyllabusModule $courseSyllabusModule,
        CourseWorkImportTemplateService $templateService,
    ): BinaryFileResponse {
        $this->authorize('import', CourseWorkMark::class);
        $this->courseWorkAccess->assertCanAccessClassModule(
            auth()->user(),
            (int) $academicCalendarClass->id,
            (int) $courseSyllabusModule->id,
        );

        $academicCalendarClass->loadMissing('classConfig');
        $classConfigId = (int) $academicCalendarClass->class_config_id;

        $data = $templateService->assembleForClassConfig($classConfigId, (int) $courseSyllabusModule->id);
        $fileName = $templateService->downloadFileName($data);

        return Excel::download(new CourseWorkImportTemplateExport($data), $fileName);
    }

    public function importPreview(
        CourseWorkImportPreviewRequest $request,
        AcademicCalendarClass $academicCalendarClass,
        CourseSyllabusModule $courseSyllabusModule,
        CourseWorkImportService $importService,
    ): JsonResponse {
        $this->authorize('import', CourseWorkMark::class);
        $this->courseWorkAccess->assertCanAccessClassModule(
            auth()->user(),
            (int) $academicCalendarClass->id,
            (int) $courseSyllabusModule->id,
        );

        $moduleId = (int) $request->validated('module');
        abort_unless($moduleId === (int) $courseSyllabusModule->id, 422);

        $file = $request->file('file');
        abort_if($file === null, 422);

        $preview = $importService->preview(
            (int) $academicCalendarClass->class_config_id,
            $moduleId,
            $file,
        );

        return response()->json($preview);
    }

    public function importProcess(
        CourseWorkImportProcessRequest $request,
        AcademicCalendarClass $academicCalendarClass,
        CourseSyllabusModule $courseSyllabusModule,
        CourseWorkImportService $importService,
    ): RedirectResponse {
        $this->authorize('import', CourseWorkMark::class);
        $this->courseWorkAccess->assertCanAccessClassModule(
            auth()->user(),
            (int) $academicCalendarClass->id,
            (int) $courseSyllabusModule->id,
        );

        $moduleId = (int) $request->validated('module');
        abort_unless($moduleId === (int) $courseSyllabusModule->id, 422);

        $result = $importService->processFromPreview(
            (int) $academicCalendarClass->class_config_id,
            $moduleId,
            (string) $request->validated('preview_token'),
        );

        return redirect()
            ->route('teaching.classes.import', [
                'academic_calendar_class' => $academicCalendarClass->id,
                'course_syllabus_module' => $courseSyllabusModule->id,
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

    public function exportClassList(
        AcademicCalendarClass $academicCalendarClass,
        ClassListDataService $classListDataService,
        ClassListPdfService $classListPdfService,
    ): \Illuminate\Http\Response {
        $this->authorize('viewLecturerClasses');
        $this->courseWorkAccess->assertCanAccessClass(auth()->user(), (int) $academicCalendarClass->id);

        $academicCalendarClass->loadMissing('classConfig.institutionDepartment');
        $classConfig = $academicCalendarClass->classConfig;
        abort_if($classConfig === null, 404);

        $data = $classListDataService->assembleForClassConfig(
            $classConfig,
            [(int) $academicCalendarClass->id],
        );
        $viewData = $classListPdfService->assembleViewData(
            $data,
            $classConfig->institutionDepartment?->tenant_id,
        );
        $fileName = sprintf('class-list-%s-%s.pdf', $academicCalendarClass->id, time());

        return Pdf::loadView('academic-calendars.class-list', $viewData)->stream($fileName);
    }

    public function studentCourseWork(
        AcademicCalendarClass $academicCalendarClass,
        StudentEnrolment $studentEnrolment,
    ): Response {
        $this->authorize('viewAny', CourseWorkMark::class);
        $this->courseWorkAccess->assertCanAccessClass(auth()->user(), (int) $academicCalendarClass->id);

        $enrolmentInClass = AcademicCalendarStudentEnrolment::query()
            ->where('academic_calendar_class_id', $academicCalendarClass->id)
            ->where('student_enrolment_id', $studentEnrolment->id)
            ->whereNull('deleted_at')
            ->exists();

        abort_unless($enrolmentInClass, 404);

        $detail = $this->teachingListService->classDetailFor(auth()->user(), $academicCalendarClass);
        abort_if($detail === null, 403);

        $studentEnrolment->loadMissing(['student.user']);
        $student = $studentEnrolment->student;
        $user = $student?->user;

        $academicCalendar = Helper::resolveAcademicCalendar();
        $allowedModuleIds = $this->courseWorkAccess->allowedModuleIdsForClass(
            auth()->user(),
            (int) $academicCalendarClass->id,
        );

        return Inertia::render('teaching/classes/StudentCourseWork', [
            'classDetail' => $detail,
            'student' => [
                'studentEnrolmentId' => (int) $studentEnrolment->id,
                'studentId' => (int) ($student?->id ?? 0),
                'studentName' => trim(sprintf('%s %s', $user?->first_name ?? '', $user?->last_name ?? '')),
                'studentNumber' => $student?->student_number,
            ],
            'allowedModuleIds' => $allowedModuleIds,
            'academicCalendar' => AcademicCalendarResource::make($academicCalendar),
            'academicContextSubtitle' => $this->academicContextSubtitle($academicCalendar),
            'canCreateCourseWork' => auth()->user()?->can('create', CourseWorkMark::class) ?? false,
            'canUpdateCourseWork' => auth()->user()?->can('update', CourseWorkMark::class) ?? false,
        ]);
    }

    private function canEnterMarks(): bool
    {
        $user = auth()->user();

        return ($user?->can('viewAny', CourseWorkMark::class) ?? false)
            || ($user?->can('create', CourseWorkMark::class) ?? false)
            || ($user?->can('update', CourseWorkMark::class) ?? false);
    }

    private function academicContextSubtitle(mixed $academicCalendar): string
    {
        return __('dashboard.academic_context_subtitle', [
            'calendar_year' => $academicCalendar->calendar_year,
            'period' => AcademicCalendarPeriodResolver::displayPeriodLabel($academicCalendar),
            'date_range' => AcademicCalendarPeriodResolver::dateRangeLabel($academicCalendar),
        ]);
    }
}
