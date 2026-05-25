<?php

namespace App\Http\Controllers\Institution\Departments;

use App\Helpers\DropdownHelper;
use App\Helpers\Helper;
use App\Http\Resources\Enrolments\EnrolmentGroupResource;
use App\Models\Institution\DepartmentCourse;
use App\Services\DepartmentEnrolmentService;
use App\DTO\Institution\{DepartmentLevelDto, DepartmentLevelRequirementsDto};
use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\{DepartmentLevelRequest, DepartmentLevelRequirementRequest};
use App\Http\Resources\Institution\{
    DepartmentLevelResource,
    InstitutionDepartmentResource,
    DepartmentApplicationStepResource,
    DepartmentLevelRequirementResource,
    IntakePeriodResource,
    ModeOfStudyResource
};
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Institution\interface\IDepartmentLevelRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response;
use App\Helpers\WorkflowHelper;

class DepartmentLevelController extends Controller
{
    public function __construct(protected IDepartmentLevelRepository $repository, protected DepartmentEnrolmentService $departmentEnrolmentService)
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
        $this->authorize('viewDepartmentMetaData');

        [$intakePeriodId, $modeOfStudyId, $courseId] = $this->departmentEnrolmentService->extractFilters();

        // ------------------------------------------------------------
        // 1. Resolve static/cached data
        // ------------------------------------------------------------
        $intakePeriods = DropdownHelper::getIntakePeriods();
        $modesOfStudy = DropdownHelper::getModesOfStudy();

        $intakePeriod = $intakePeriodId
            ? $intakePeriods->firstWhere('id', $intakePeriodId)
            : Helper::resolveIntakePeriod();

        $modeOfStudy = $modeOfStudyId
            ? $modesOfStudy->firstWhere('id', $modeOfStudyId)
            : Helper::resolveModeOfStudy();

        $departmentCourse = $courseId
            ? DepartmentCourse::with(['course'])->find($courseId)
            : null;

        // ------------------------------------------------------------
        // 2. Query enrolments efficiently
        // ------------------------------------------------------------
        $results = $this->departmentEnrolmentService->queryEnrolments($institutionDepartment->id, $departmentLevel->id, $intakePeriod->id, $modeOfStudy->id, $courseId);

        // ------------------------------------------------------------
        // 3. Prepare data for Inertia
        // ------------------------------------------------------------
        return Inertia::render('institution/enrolments/CourseLevelEnrolments', [
            'department' => InstitutionDepartmentResource::make($institutionDepartment),
            'level' => DepartmentLevelResource::make($departmentLevel),
            'intakePeriod' => IntakePeriodResource::make($intakePeriod),
            'modeOfStudy' => ModeOfStudyResource::make($modeOfStudy),
            'workflowSteps' => DepartmentApplicationStepResource::collection(WorkflowHelper::getAllSteps($institutionDepartment->id)),
            'classSize' => $courseId
                ? $this->departmentEnrolmentService->getClassSize($institutionDepartment, $departmentLevel->id, $courseId, $intakePeriod->id, $modeOfStudy->id)
                : 0,
            'enrolments' => EnrolmentGroupResource::make($results),
            'modesOfStudy' => ModeOfStudyResource::collection($modesOfStudy),
            'intakePeriods' => IntakePeriodResource::collection($intakePeriods),
            'course' => $departmentCourse ? ['name' => $departmentCourse?->course?->name, 'department_course_id' => $courseId] : null,
        ]);
    }
}
