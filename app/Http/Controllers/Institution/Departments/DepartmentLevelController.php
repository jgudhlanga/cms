<?php

namespace App\Http\Controllers\Institution\Departments;

use App\Enums\Institution\ModeOfStudyEnum;
use App\Models\Institution\ModeOfStudy;
use App\DTO\Institution\{DepartmentLevelDto, DepartmentLevelRequirementsDto};
use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\{DepartmentLevelRequest, DepartmentLevelRequirementRequest};
use App\Http\Resources\Enrolments\EnrolmentResource;
use App\Http\Resources\Institution\{DepartmentLevelResource,
    InstitutionDepartmentResource,
    DepartmentApplicationStepResource,
    DepartmentLevelRequirementResource,
    IntakePeriodResource,
    ModeOfStudyResource
};
use App\Models\Institution\DepartmentLevel;
use App\Models\Students\StudentProgram;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Institution\interface\IDepartmentLevelRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response;
use App\Helpers\WorkflowHelper;
use App\Models\Institution\IntakePeriod;

class DepartmentLevelController extends Controller
{
    public function __construct(protected IDepartmentLevelRepository $repository)
    {
    }

    /**
     * @throws AuthorizationException
     */
    public function departmentLevelRequirements(DepartmentLevel $departmentLevel): Response
    {
        $this->authorize('updateDepartmentMetaData');
        $departmentLevel = DepartmentLevelResource::make($departmentLevel);
        $institutionDepartment = InstitutionDepartmentResource::make($departmentLevel->institutionDepartment);
        $levels = DepartmentLevelResource::collection($institutionDepartment->departmentLevels);
        $requirements = $departmentLevel->requirement ? DepartmentLevelRequirementResource::make($departmentLevel->requirement) : null;
        return Inertia::render('institution/departments/DepartmentLevelRequirements',
            compact('departmentLevel', 'institutionDepartment', 'levels', 'requirements'));
    }

    /**
     * @throws AuthorizationException
     */
    public function updateDepartmentLevelRequirements(DepartmentLevel $departmentLevel, DepartmentLevelRequirementRequest $request): void
    {
        $this->authorize('updateDepartmentMetaData');
        $this->repository->updateDepartmentLevelRequirements($departmentLevel, DepartmentLevelRequirementsDto::fromDepartmentLevelRequirementRequest($request));
    }

    /**
     * @throws AuthorizationException
     */
    public function syncDepartmentLevels(InstitutionDepartment $institutionDepartment, DepartmentLevelRequest $request): void
    {
        $this->authorize('createDepartmentMetaData');
        $this->repository->syncDepartmentLevels($institutionDepartment, DepartmentLevelDto::fromDepartmentLevelRequest($request));
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(DepartmentLevel $departmentLevel): void
    {
        $this->authorize('deleteDepartmentMetaData');
        $this->repository->delete($departmentLevel);
    }

    /**
     * @throws AuthorizationException
     */
    public function restore(string $id): void
    {
        $departmentLevel = $this->repository->findTrashed($id);
        $this->authorize('restoreDepartmentMetaData');
        $this->repository->restore($departmentLevel);
    }

    /**
     * @throws AuthorizationException
     */
    public function forceDelete(DepartmentLevel $departmentLevel): void
    {
        $this->authorize('forceDeleteDepartmentMetaData');
        $this->repository->delete($departmentLevel, true);
    }

    /**
     * @throws AuthorizationException
     */
    public function enrolments(InstitutionDepartment $institutionDepartment, DepartmentLevel $departmentLevel): Response
    {
        $this->authorize('viewAnyDepartmentMetaData');
        [$intakePeriodId, $modeOfStudyId] = $this->extractFilters();

        $intakePeriod = $this->resolveIntakePeriod($intakePeriodId);
        $modeOfStudy = $this->resolveModeOfStudy($modeOfStudyId);

        $workflowSteps = DepartmentApplicationStepResource::collection(
            WorkflowHelper::getAllSteps($institutionDepartment->id)
        );

        $maxStep = WorkflowHelper::getMaxStep($institutionDepartment->id);

        $enrolments = $this->fetchEnrolments($institutionDepartment, $departmentLevel, $intakePeriodId, $modeOfStudyId, $maxStep);

        return Inertia::render('institution/enrolments/CourseLevelEnrolments', [
            'department' => InstitutionDepartmentResource::make($institutionDepartment),
            'level' => DepartmentLevelResource::make($departmentLevel),
            'intakePeriod' => IntakePeriodResource::make($intakePeriod),
            'modeOfStudy' => ModeOfStudyResource::make($modeOfStudy),
            'workflowSteps' => $workflowSteps,
            'enrolments' => $enrolments,
        ]);
    }

    private function extractFilters(): array
    {
        $intakePeriodId = request('intake_period_id') > 0 ? (int)request('intake_period_id') : null;
        $modeOfStudyId = request('mode_of_study_id') > 0 ? (int)request('mode_of_study_id') : null;

        return [$intakePeriodId, $modeOfStudyId];
    }

    private function resolveIntakePeriod(?int $intakePeriodId): ?IntakePeriod
    {
        return $intakePeriodId
            ? IntakePeriod::find($intakePeriodId)
            : IntakePeriod::orderByDesc('end_date')->first();
    }

    private function resolveModeOfStudy(?int $modeOfStudyId): ?ModeOfStudy
    {
        return $modeOfStudyId
            ? ModeOfStudy::find($modeOfStudyId)
            : ModeOfStudy::where('name', ModeOfStudyEnum::FULL_TIME->value)->first();
    }

    private function fetchEnrolments(InstitutionDepartment $institutionDepartment, DepartmentLevel $departmentLevel, ?int $intakePeriodId, ?int $modeOfStudyId, $maxStep)
    {
        $query = $institutionDepartment->enrolments()
            ->where('department_level_id', $departmentLevel->id)
            ->whereHas('departmentWorkflowStep', fn($q) => $q->where('position', '<', $maxStep->position)
            )
            ->when($intakePeriodId, fn($q) => $q->where('intake_period_id', $intakePeriodId))
            ->when($modeOfStudyId, fn($q) => $q->where('mode_of_study_id', $modeOfStudyId))
            ->with([
                'departmentWorkflowStep',
                'student.user',
                'institutionDepartment.department',
                'departmentLevel.level',
                'departmentCourse.course',
                'student.oLevelResults',
            ])
            ->orderBy('student_programs.created_at');

        return $query->get()
            ->groupBy(fn($enrolment) => $enrolment->departmentWorkflowStep->workflowStep->name)
            ->sortByDesc(fn($group) => $group->first()->departmentWorkflowStep->position ?? 0)
            ->map(fn($group) => EnrolmentResource::collection($group));
    }

}
