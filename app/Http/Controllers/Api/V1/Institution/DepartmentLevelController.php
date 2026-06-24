<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Enums\Institution\ModeOfStudyEnum;
use App\Helpers\WorkflowHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Enrolments\EnrolmentResource;
use App\Http\Resources\Institution\DepartmentApplicationStepResource;
use App\Http\Resources\Institution\DepartmentLevelRequirementResource;
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\ModeOfStudy;
use Illuminate\Http\Request;

class DepartmentLevelController extends Controller
{
    public function index(InstitutionDepartment $institutionDepartment)
    {
        $levels = DepartmentLevel::where('institution_department_id', $institutionDepartment->id)
            ->where('show_on_current_application_period', true)
            ->select('*')
            ->orderBy('level_id', 'asc')
            ->orderBy('created_at')
            ->orderBy('deleted_at')
            ->get();
        //return DepartmentLevelResource::collection($institutionDepartment->departmentLevels);
        return DepartmentLevelResource::collection($levels);
    }

    public function levelRequirements(DepartmentLevel $departmentLevel)
    {
        return $departmentLevel->requirement ? DepartmentLevelRequirementResource::make($departmentLevel->requirement) : null;
    }

    public function store(Request $request)
    {
    }

    public function show(string $id)
    {
    }

    public function update(Request $request, string $id)
    {
    }

    public function destroy(string $id)
    {
    }

    public function enrolments(InstitutionDepartment $institutionDepartment, DepartmentLevel $departmentLevel)
    {
        $this->authorize('viewAnyDepartmentMetaData');
        [$intakePeriodId, $modeOfStudyId, $courseId] = $this->extractFilters();

        $intakePeriod = $this->resolveIntakePeriod($intakePeriodId);
        $modeOfStudy = $this->resolveModeOfStudy($modeOfStudyId);

        $workflowSteps = DepartmentApplicationStepResource::collection(
            WorkflowHelper::getAllSteps($institutionDepartment->id)
        );

        $maxStep = WorkflowHelper::getMaxStep($institutionDepartment->id);
        $enrolments = $this->fetchEnrolments($institutionDepartment, $departmentLevel, $intakePeriodId, $modeOfStudyId, $maxStep, $courseId);
    }

    private function extractFilters(): array
    {
        $intakePeriodId = request('intake_period_id') > 0 ? (int)request('intake_period_id') : null;
        $modeOfStudyId = request('mode_of_study_id') > 0 ? (int)request('mode_of_study_id') : null;
        $courseId = request('department_course_id') > 0 ? (int)request('department_course_id') : null;

        return [$intakePeriodId, $modeOfStudyId, $courseId];
    }

    private function resolveIntakePeriod(?int $intakePeriodId)
    {
        return $intakePeriodId
            ? IntakePeriod::find($intakePeriodId)
            : IntakePeriod::orderByDesc('end_date')->first();
    }

    private function resolveModeOfStudy(?int $modeOfStudyId)
    {
        return $modeOfStudyId
            ? ModeOfStudy::find($modeOfStudyId)
            : ModeOfStudy::where('name', ModeOfStudyEnum::FULL_TIME->value)->first();
    }

    private function fetchEnrolments(
        InstitutionDepartment $institutionDepartment,
        DepartmentLevel       $departmentLevel,
        ?int                  $intakePeriodId,
        ?int                  $modeOfStudyId,
                              $maxStep, $courseId)
    {
        $query = $institutionDepartment->studentApplications()
            ->where('department_level_id', $departmentLevel->id)
            ->whereHas('departmentWorkflowStep', fn($q) => $q->where('position', '<', $maxStep->position))
            ->when($intakePeriodId, fn($q) => $q->where('intake_period_id', $intakePeriodId))
            ->when($modeOfStudyId, fn($q) => $q->where('mode_of_study_id', $modeOfStudyId))
            ->when($courseId, fn($q) => $q->where('department_course_id', $courseId))
            ->with([
                'departmentWorkflowStep',
                'student.user',
                'institutionDepartment.department',
                'departmentLevel.level',
                'departmentCourse.course',
                'student.oLevelResults',
            ])
            ->orderBy('student_applications.created_at');

        return $query->get()
            ->groupBy(fn($enrolment) => $enrolment->departmentWorkflowStep->workflowStep->name)
            ->sortByDesc(fn($group) => $group->first()->departmentWorkflowStep->position ?? 0)
            ->map(fn($group) => EnrolmentResource::collection($group));
    }
}
