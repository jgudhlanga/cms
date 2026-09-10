<?php

declare(strict_types=1);

namespace App\Http\Controllers\AcademicCalendars;

use App\Enums\AcademicCalendars\EnrolmentProgressionImportAction;
use App\Exports\AcademicCalendars\EnrolmentProgressionImportTemplateExport;
use App\Http\Controllers\Concerns\ResolvesAcademicCalendarFromCalendarYear;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicCalendars\EnrolmentProgressionImportPreviewRequest;
use App\Http\Requests\AcademicCalendars\EnrolmentProgressionImportProcessRequest;
use App\Http\Resources\AcademicCalendars\AcademicCalendarResource;
use App\Http\Resources\AcademicCalendars\ClassConfigResource;
use App\Http\Resources\Institution\DepartmentCourseResource;
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Http\Resources\Institution\ModeOfStudyResource;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\ModeOfStudy;
use App\Services\AcademicCalendars\EnrolmentProgressionImportService;
use App\Services\AcademicCalendars\EnrolmentProgressionImportTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EnrolmentProgressionImportController extends Controller
{
    use ResolvesAcademicCalendarFromCalendarYear;

    public function show(
        Request $request,
        InstitutionDepartment $institutionDepartment,
        string $calendar_year,
        string $action,
    ): Response {
        $this->authorize('update:academic-calendar-student-enrolments');
        $importAction = $this->resolveAction($action);
        $academicCalendar = $this->academicCalendarFromCalendarYear($calendar_year);
        $classConfig = $this->resolveClassConfig($institutionDepartment, $academicCalendar, $request);
        $restrictToClass = $this->resolveOptionalClass($request, $classConfig);

        $course = $classConfig->departmentCourse ?? DepartmentCourse::query()->find($classConfig->department_course_id);
        $level = $classConfig->departmentLevel ?? DepartmentLevel::query()->find($classConfig->department_level_id);
        $mode = $classConfig->modeOfStudy ?? ModeOfStudy::query()->find($classConfig->mode_of_study_id);

        return Inertia::render('institution/academicCalendars/DepartmentAcademicCalendarProgressionImport', [
            'department' => InstitutionDepartmentResource::make($institutionDepartment),
            'academicCalendar' => AcademicCalendarResource::make($academicCalendar),
            'course' => DepartmentCourseResource::make($course),
            'level' => DepartmentLevelResource::make($level),
            'mode' => ModeOfStudyResource::make($mode),
            'classConfig' => ClassConfigResource::make($classConfig),
            'classConfigQuery' => $this->classConfigQueryParams($classConfig, $request),
            'action' => $importAction->value,
            'actionLabel' => $importAction->label(),
            'academicCalendarClassId' => $restrictToClass?->id,
            'academicCalendarClassName' => $restrictToClass?->name,
            'importResult' => session('progressionImportResult'),
        ]);
    }

    public function template(
        Request $request,
        InstitutionDepartment $institutionDepartment,
        string $calendar_year,
        string $action,
        EnrolmentProgressionImportTemplateService $templateService,
    ): BinaryFileResponse {
        $this->authorize('update:academic-calendar-student-enrolments');
        $importAction = $this->resolveAction($action);
        $academicCalendar = $this->academicCalendarFromCalendarYear($calendar_year);
        $classConfig = $this->resolveClassConfig($institutionDepartment, $academicCalendar, $request);
        $restrictToClass = $this->resolveOptionalClass($request, $classConfig);

        $data = $templateService->assemble(
            $institutionDepartment,
            $classConfig,
            $importAction,
            $restrictToClass,
        );
        $fileName = $templateService->downloadFileName($institutionDepartment, $classConfig, $importAction);

        return Excel::download(
            new EnrolmentProgressionImportTemplateExport($data, $importAction, $institutionDepartment),
            $fileName,
        );
    }

    public function preview(
        EnrolmentProgressionImportPreviewRequest $request,
        InstitutionDepartment $institutionDepartment,
        string $calendar_year,
        string $action,
        EnrolmentProgressionImportService $importService,
    ): JsonResponse {
        $this->authorize('update:academic-calendar-student-enrolments');
        $importAction = $this->resolveAction($action);
        $academicCalendar = $this->academicCalendarFromCalendarYear($calendar_year);
        $classConfig = $this->resolveClassConfig($institutionDepartment, $academicCalendar, $request);
        $restrictToClass = $this->resolveOptionalClass($request, $classConfig);

        $file = $request->file('file');
        abort_if($file === null, 422);

        try {
            $preview = $importService->preview(
                $file,
                $institutionDepartment,
                $classConfig,
                $importAction,
                $restrictToClass,
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json($preview);
    }

    public function process(
        EnrolmentProgressionImportProcessRequest $request,
        InstitutionDepartment $institutionDepartment,
        string $calendar_year,
        string $action,
        EnrolmentProgressionImportService $importService,
    ): RedirectResponse {
        $this->authorize('update:academic-calendar-student-enrolments');
        $importAction = $this->resolveAction($action);
        $academicCalendar = $this->academicCalendarFromCalendarYear($calendar_year);
        $classConfig = $this->resolveClassConfig($institutionDepartment, $academicCalendar, $request);
        $restrictToClass = $this->resolveOptionalClass($request, $classConfig);

        $result = $importService->process(
            $request->validated('rows'),
            $institutionDepartment,
            $classConfig,
            $importAction,
            $restrictToClass,
            auth()->id(),
        );

        $message = match ($importAction) {
            EnrolmentProgressionImportAction::CompleteLevel => __('academic_calendar.complete_level_success', [
                'count' => $result['processed'],
            ]),
            EnrolmentProgressionImportAction::AdvancePhase => __('academic_calendar.advance_phase_success', [
                'count' => $result['processed'],
            ]),
        };

        if ($result['processed'] < 1) {
            return back()->withErrors([
                'rows' => match ($importAction) {
                    EnrolmentProgressionImportAction::CompleteLevel => __('academic_calendar.complete_level_none'),
                    EnrolmentProgressionImportAction::AdvancePhase => __('academic_calendar.advance_phase_none'),
                },
            ]);
        }

        return back()->with([
            'success' => $message,
            'progressionImportResult' => $result,
        ]);
    }

    private function resolveAction(string $action): EnrolmentProgressionImportAction
    {
        $resolved = EnrolmentProgressionImportAction::tryFrom($action);
        abort_unless($resolved instanceof EnrolmentProgressionImportAction, 404);

        return $resolved;
    }

    private function resolveClassConfig(
        InstitutionDepartment $institutionDepartment,
        AcademicCalendar $academicCalendar,
        Request $request,
    ): ClassConfig {
        $classConfigId = (int) $request->query('class_config_id', $request->input('class_config_id', 0));
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
                    ->where('mode_of_study_id', $modeOfStudyId);
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

    private function resolveOptionalClass(Request $request, ClassConfig $classConfig): ?AcademicCalendarClass
    {
        $classId = (int) ($request->query('academic_calendar_class_id')
            ?? $request->input('academic_calendar_class_id')
            ?? 0);

        if ($classId < 1) {
            return null;
        }

        $class = AcademicCalendarClass::query()->find($classId);
        abort_unless(
            $class instanceof AcademicCalendarClass
            && (int) $class->class_config_id === (int) $classConfig->id,
            404,
        );

        return $class;
    }

    /**
     * @return array<string, string>
     */
    private function classConfigQueryParams(ClassConfig $classConfig, Request $request): array
    {
        return array_filter([
            'class_config_id' => (string) $classConfig->id,
            'department_course_id' => $request->query('department_course_id')
                ? (string) $request->query('department_course_id')
                : (string) $classConfig->department_course_id,
            'department_level_id' => $request->query('department_level_id')
                ? (string) $request->query('department_level_id')
                : (string) $classConfig->department_level_id,
            'mode_of_study_id' => $request->query('mode_of_study_id')
                ? (string) $request->query('mode_of_study_id')
                : (string) $classConfig->mode_of_study_id,
            'academic_calendar_class_id' => $request->query('academic_calendar_class_id')
                ? (string) $request->query('academic_calendar_class_id')
                : null,
        ]);
    }
}
