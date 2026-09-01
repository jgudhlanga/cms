<?php

namespace App\Http\Controllers\AcademicCalendars;

use App\Actions\AcademicCalendars\AddStudentsToAcademicCalendarClassAction;
use App\Actions\AcademicCalendars\AssignAcademicCalendarClassTutorAction;
use App\Actions\AcademicCalendars\CopySyllabusModuleLecturersToClassAction;
use App\Actions\AcademicCalendars\RemoveStudentsFromAcademicCalendarClassAction;
use App\Actions\AcademicCalendars\SyncAcademicCalendarClassModuleLecturersAction;
use App\Http\Controllers\Concerns\ResolvesAcademicCalendarFromCalendarYear;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicCalendars\AddAcademicCalendarClassStudentsRequest;
use App\Http\Requests\AcademicCalendars\AssignAcademicCalendarClassTutorRequest;
use App\Http\Requests\AcademicCalendars\BulkAcademicCalendarClassStudentsRequest;
use App\Http\Requests\AcademicCalendars\CopySyllabusModuleLecturersToClassRequest;
use App\Http\Requests\AcademicCalendars\SyncAcademicCalendarClassModuleLecturersRequest;
use App\Http\Requests\AcademicCalendars\UpdateAcademicCalendarClassRequest;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Queries\AcademicCalendars\UnassignedClassConfigStudentsQuery;
use App\Services\AcademicCalendars\ClassStaffingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class AcademicCalendarClassController extends Controller
{
    use ResolvesAcademicCalendarFromCalendarYear;

    public function update(
        UpdateAcademicCalendarClassRequest $request,
        InstitutionDepartment $institutionDepartment,
        string $calendar_year,
        AcademicCalendarClass $academicCalendarClass
    ): RedirectResponse {
        $academicCalendar = $this->academicCalendarFromCalendarYear($calendar_year);

        $this->authorize('update', $academicCalendar);

        $academicCalendarClass->loadMissing('classConfig');
        $classConfig = $academicCalendarClass->classConfig;

        abort_unless(
            $classConfig instanceof ClassConfig
            && (int) $classConfig->institution_department_id === (int) $institutionDepartment->id
            && (string) $classConfig->calendar_year === (string) $academicCalendar->calendar_year,
            404
        );

        $validated = $request->validated();

        $academicCalendarClass->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', __('academic_calendar.update_class_success'));
    }

    public function assignTutor(
        AssignAcademicCalendarClassTutorRequest $request,
        InstitutionDepartment $institutionDepartment,
        string $calendar_year,
        AcademicCalendarClass $academicCalendarClass,
        AssignAcademicCalendarClassTutorAction $assignTutorAction,
    ): RedirectResponse {
        $academicCalendar = $this->academicCalendarFromCalendarYear($calendar_year);

        $this->authorize('update', $academicCalendar);

        $academicCalendarClass->loadMissing('classConfig');
        $classConfig = $academicCalendarClass->classConfig;

        abort_unless(
            $classConfig instanceof ClassConfig
            && (int) $classConfig->institution_department_id === (int) $institutionDepartment->id
            && (string) $classConfig->calendar_year === (string) $academicCalendar->calendar_year,
            404
        );

        $tenantId = (int) ($academicCalendarClass->tenant_id ?? auth()->user()?->tenant_id);

        $assignTutorAction->execute(
            $academicCalendarClass,
            $request->validated('staff_id'),
            $tenantId,
        );

        $staffId = $request->validated('staff_id');

        return back()->with(
            'success',
            $staffId === null
                ? __('academic_calendar.tutor_removed_success')
                : __('academic_calendar.tutor_assigned_success'),
        );
    }

    public function syncModuleLecturers(
        SyncAcademicCalendarClassModuleLecturersRequest $request,
        InstitutionDepartment $institutionDepartment,
        string $calendar_year,
        AcademicCalendarClass $academicCalendarClass,
        SyncAcademicCalendarClassModuleLecturersAction $syncModuleLecturersAction,
        ClassStaffingService $classStaffingService,
    ): RedirectResponse|JsonResponse {
        $academicCalendar = $this->academicCalendarFromCalendarYear($calendar_year);

        $this->authorize('update', $academicCalendar);

        $academicCalendarClass->loadMissing('classConfig');
        $classConfig = $academicCalendarClass->classConfig;

        abort_unless(
            $classConfig instanceof ClassConfig
            && (int) $classConfig->institution_department_id === (int) $institutionDepartment->id
            && (string) $classConfig->calendar_year === (string) $academicCalendar->calendar_year,
            404
        );

        $validated = $request->validated();
        $module = CourseSyllabusModule::query()->findOrFail((int) $validated['course_syllabus_module_id']);
        $tenantId = (int) ($academicCalendarClass->tenant_id ?? auth()->user()?->tenant_id);

        $staffIds = array_map('intval', $validated['staff_ids'] ?? []);

        $syncModuleLecturersAction->execute(
            $academicCalendarClass,
            $classConfig,
            $module,
            $staffIds,
            $tenantId,
        );

        if ($request->wantsJson()) {
            $uniqueStaffIds = array_values(array_unique($staffIds));

            return response()->json([
                'message' => __('academic_calendar.module_lecturers_saved_for_module', ['code' => $module->code]),
                'moduleId' => (int) $module->id,
                'staffIds' => $uniqueStaffIds,
                'staffNames' => $classStaffingService->orderedStaffNames($uniqueStaffIds),
            ]);
        }

        return back()->with('success', __('academic_calendar.module_lecturers_saved_success'));
    }

    public function copyModuleLecturerDefaults(
        CopySyllabusModuleLecturersToClassRequest $request,
        InstitutionDepartment $institutionDepartment,
        string $calendar_year,
        AcademicCalendarClass $academicCalendarClass,
        CopySyllabusModuleLecturersToClassAction $copyDefaultsAction,
        ClassStaffingService $classStaffingService,
    ): RedirectResponse|JsonResponse {
        $academicCalendar = $this->academicCalendarFromCalendarYear($calendar_year);

        $this->authorize('update', $academicCalendar);

        $academicCalendarClass->loadMissing('classConfig');
        $classConfig = $academicCalendarClass->classConfig;

        abort_unless(
            $classConfig instanceof ClassConfig
            && (int) $classConfig->institution_department_id === (int) $institutionDepartment->id
            && (string) $classConfig->calendar_year === (string) $academicCalendar->calendar_year,
            404
        );

        $tenantId = (int) ($academicCalendarClass->tenant_id ?? auth()->user()?->tenant_id);

        $copyDefaultsAction->execute(
            $academicCalendarClass,
            $classConfig,
            $tenantId,
        );

        if ($request->wantsJson()) {
            $semesterConfig = $classStaffingService->classConfigForStaffing($classConfig);
            $modules = $semesterConfig instanceof ClassConfig
                ? $classStaffingService->resolveSemesterModules($semesterConfig)
                : collect();
            $classId = (int) $academicCalendarClass->id;
            $classModuleStaffIdsByClassId = $classStaffingService->classModuleStaffIdsByClassId([$classId], $modules);
            $templateStaffIdsByModuleId = $classStaffingService->templateStaffIdsByModuleId($modules);

            return response()->json([
                'message' => __('academic_calendar.module_lecturers_copied_success'),
                'semesterModules' => $classStaffingService->buildSemesterModulesPayload(
                    $academicCalendarClass,
                    $modules,
                    $classModuleStaffIdsByClassId,
                    $templateStaffIdsByModuleId,
                ),
            ]);
        }

        return back()->with('success', __('academic_calendar.module_lecturers_copied_success'));
    }

    public function addStudents(
        AddAcademicCalendarClassStudentsRequest $request,
        InstitutionDepartment $institutionDepartment,
        string $calendar_year,
        AcademicCalendarClass $academicCalendarClass,
        AddStudentsToAcademicCalendarClassAction $addStudentsAction,
        UnassignedClassConfigStudentsQuery $unassignedStudentsQuery,
    ): RedirectResponse {
        $this->authorize('update:academic-calendar-student-enrolments');

        $academicCalendar = $this->academicCalendarFromCalendarYear($calendar_year);
        $classConfig = $this->classConfigForDepartmentClass(
            $academicCalendarClass,
            $institutionDepartment,
            (string) $academicCalendar->calendar_year,
        );

        /** @var array<int, int> $studentEnrolmentIds */
        $studentEnrolmentIds = array_map('intval', $request->validated('student_enrolment_ids'));

        $eligibleRows = $unassignedStudentsQuery->eligibleRowsForEnrolmentIds(
            $institutionDepartment,
            $classConfig,
            (string) $academicCalendar->calendar_year,
            $studentEnrolmentIds,
        );

        $tenantId = (int) ($academicCalendarClass->tenant_id ?? auth()->user()?->tenant_id);

        $added = $addStudentsAction->execute(
            $academicCalendarClass,
            $classConfig,
            $eligibleRows,
            $tenantId,
        );

        return back()->with('success', __('academic_calendar.add_students_success', ['count' => $added]));
    }

    public function removeStudents(
        BulkAcademicCalendarClassStudentsRequest $request,
        InstitutionDepartment $institutionDepartment,
        string $calendar_year,
        AcademicCalendarClass $academicCalendarClass,
        RemoveStudentsFromAcademicCalendarClassAction $removeStudentsAction,
    ): RedirectResponse {
        $this->authorize('update:academic-calendar-student-enrolments');

        $academicCalendar = $this->academicCalendarFromCalendarYear($calendar_year);
        $this->classConfigForDepartmentClass(
            $academicCalendarClass,
            $institutionDepartment,
            (string) $academicCalendar->calendar_year,
        );

        /** @var array<int, int> $studentEnrolmentIds */
        $studentEnrolmentIds = array_map('intval', $request->validated('student_enrolment_ids'));

        $enrolments = AcademicCalendarStudentEnrolment::query()
            ->where('academic_calendar_class_id', $academicCalendarClass->id)
            ->whereIn('student_enrolment_id', $studentEnrolmentIds)
            ->get();

        abort_if($enrolments->isEmpty(), 422);

        $this->authorize('update', $enrolments->first());

        $removed = $removeStudentsAction->execute($enrolments);

        return back()->with('success', __('academic_calendar.remove_students_success', ['count' => $removed]));
    }

    private function classConfigForDepartmentClass(
        AcademicCalendarClass $academicCalendarClass,
        InstitutionDepartment $institutionDepartment,
        string $calendarYear,
    ): ClassConfig {
        $academicCalendarClass->loadMissing('classConfig');
        $classConfig = $academicCalendarClass->classConfig;

        abort_unless(
            $classConfig instanceof ClassConfig
            && (int) $classConfig->institution_department_id === (int) $institutionDepartment->id
            && (string) $classConfig->calendar_year === $calendarYear,
            404,
        );

        return $classConfig;
    }
}
