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
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Institution\interface\IDepartmentApplicationStepRepository;
use Inertia\Inertia;

class DepartmentApplicationStepController extends Controller
{
    public function __construct(protected IDepartmentApplicationStepRepository $repository)
    {
    }

    public function index(InstitutionDepartment $institutionDepartment)
    {
        $this->authorize('viewDepartmentMetaData');;
        $institutionDepartment = InstitutionDepartmentResource::make($institutionDepartment);
        $steps = $institutionDepartment->applicationSteps()->orderBy('position')->get();
        $departmentApplicationSteps = DepartmentApplicationStepResource::collection($steps);
        return Inertia::render('institution/departments/ApplicationStepConfig',
            compact('institutionDepartment', 'departmentApplicationSteps'),
        );
    }

    public function syncApplicationSteps(InstitutionDepartment $institutionDepartment, DepartmentApplicationStepRequest $request): void
    {
        $this->authorize('createDepartmentMetaData');
        $this->repository->syncDepartmentApplicationSteps($institutionDepartment, DepartmentApplicationStepDto::fromDepartmentApplicationStepRequest($request));
    }

    public function syncWorkflowStepActionMetadata(InstitutionDepartment $institutionDepartment, WorkflowStepActionMetadataRequest $request): void
    {
        $this->authorize('createDepartmentMetaData');
        $this->repository->syncWorkflowStepActionMetadata(WorkflowStepActionMetadataDto::fromWorkflowStepActionMetadataRequest($request));
    }

    public function show(DepartmentApplicationStep $departmentApplicationStep)
    {
        $this->authorize('viewDepartmentMetaData');
        $departmentApplicationStep = DepartmentApplicationStepResource::make($departmentApplicationStep);
        $institutionDepartment = InstitutionDepartmentResource::make($departmentApplicationStep->institutionDepartment);
        $departmentLevels = DepartmentLevelResource::collection($departmentApplicationStep->institutionDepartment->departmentLevels);;
        return Inertia::render('institution/departments/courses/Edit',
            compact('institutionDepartment', 'departmentApplicationStep', 'departmentLevels'),
        );
    }

    public function update(DepartmentApplicationStep $departmentApplicationStep, DepartmentApplicationStepUpdateRequest $request): void
    {
        $this->authorize('updateDepartmentMetaData');
        $this->repository->update($departmentApplicationStep, DepartmentApplicationStepUpdateDto::fromDepartmentApplicationStepUpdateRequest($request));
    }

    public function destroy(DepartmentApplicationStep $departmentApplicationStep)
    {
        $this->authorize('deleteDepartmentMetaData');
        $this->repository->delete($departmentApplicationStep);
    }

    public function restore(string $id)
    {
        $departmentApplicationStep = $this->repository->findTrashed($id);
        $this->authorize('restoreDepartmentMetaData');
        $this->repository->restore($departmentApplicationStep);
    }

    public function forceDelete(DepartmentApplicationStep $departmentApplicationStep)
    {
        $this->authorize('forceDeleteDepartmentMetaData');
        $this->repository->delete($departmentApplicationStep, true);
    }
}
