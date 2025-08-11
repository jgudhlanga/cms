<?php

namespace App\Http\Controllers\Institution\Departments;

use App\DTO\Institution\DepartmentApplicationStepDto;
use App\DTO\Institution\DepartmentApplicationStepUpdateDto;
use App\DTO\Institution\WorkflowStepActionMetadataDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\DepartmentApplicationStepRequest;
use App\Http\Requests\Institution\DepartmentApplicationStepUpdateRequest;
use App\Http\Requests\Institution\WorkflowStepActionMetadataRequest;
use App\Http\Resources\Institution\DepartmentApplicationStepResource;
use App\Http\Resources\Institution\DepartmentCourseResource;
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Institution\interface\IDepartmentApplicationStepRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentApplicationStepController extends Controller
{
    public function __construct(protected IDepartmentApplicationStepRepository $repository)
    {
    }

    /**
     * @throws AuthorizationException
     */
    public function syncApplicationSteps(InstitutionDepartment $institutionDepartment, DepartmentApplicationStepRequest $request): void
    {
        $this->authorize('createDepartmentMetaData');
        $this->repository->syncDepartmentApplicationSteps($institutionDepartment, DepartmentApplicationStepDto::fromDepartmentApplicationStepRequest($request));
    }

    /**
     * @throws AuthorizationException
     */
    public function syncWorkflowStepActionMetadata(InstitutionDepartment $institutionDepartment, WorkflowStepActionMetadataRequest $request): void
    {
        $this->authorize('createDepartmentMetaData');
        $this->repository->syncWorkflowStepActionMetadata(WorkflowStepActionMetadataDto::fromWorkflowStepActionMetadataRequest($request));
    }


    /**
     * @throws AuthorizationException
     */
    public function show(DepartmentApplicationStep $departmentApplicationStep): Response
    {
        $this->authorize('viewDepartmentMetaData');
        $departmentApplicationStep = DepartmentApplicationStepResource::make($departmentApplicationStep);
        $institutionDepartment = InstitutionDepartmentResource::make($departmentApplicationStep->institutionDepartment);
        $departmentLevels = DepartmentLevelResource::collection($departmentApplicationStep->institutionDepartment->departmentLevels);
        return Inertia::render('institution/departments/courses/Edit',
            compact('institutionDepartment', 'departmentApplicationStep', 'departmentLevels'),
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function update(DepartmentApplicationStep $departmentApplicationStep, DepartmentApplicationStepUpdateRequest $request): void
    {
        $this->authorize('updateDepartmentMetaData');
        $this->repository->update($departmentApplicationStep, DepartmentApplicationStepUpdateDto::fromDepartmentApplicationStepUpdateRequest($request));
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(DepartmentApplicationStep $departmentApplicationStep): void
    {
        $this->authorize('deleteDepartmentMetaData');
        $this->repository->delete($departmentApplicationStep);
    }

    /**
     * @throws AuthorizationException
     */
    public function restore(string $id): void
    {
        $departmentApplicationStep = $this->repository->findTrashed($id);
        $this->authorize('restoreDepartmentMetaData');
        $this->repository->restore($departmentApplicationStep);
    }

    /**
     * @throws AuthorizationException
     */
    public function forceDelete(DepartmentApplicationStep $departmentApplicationStep): void
    {
        $this->authorize('forceDeleteDepartmentMetaData');
        $this->repository->delete($departmentApplicationStep, true);
    }
}
