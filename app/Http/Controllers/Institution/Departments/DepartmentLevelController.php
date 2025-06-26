<?php

namespace App\Http\Controllers\Institution\Departments;

use App\DTO\Institution\DepartmentLevelDto;
use App\DTO\Institution\DepartmentLevelRequirementsDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\DepartmentLevelRequest;
use App\Http\Requests\Institution\DepartmentLevelRequirementRequest;
use App\Http\Resources\Institution\DepartmentLevelRequirementResource;
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Institution\interface\IDepartmentLevelRepository;
use Inertia\Inertia;

class DepartmentLevelController extends Controller
{
    public function __construct(protected IDepartmentLevelRepository $repository)
    {
    }

    public function departmentLevelRequirements(DepartmentLevel $departmentLevel)
    {
        $this->authorize('updateDepartmentMetaData');
        $departmentLevel = DepartmentLevelResource::make($departmentLevel);
        $institutionDepartment = InstitutionDepartmentResource::make($departmentLevel->institutionDepartment);
        $levels = DepartmentLevelResource::collection($institutionDepartment->departmentLevels);
        $requirements = $departmentLevel->requirement ?  DepartmentLevelRequirementResource::make($departmentLevel->requirement) : null;
        return Inertia::render('institution/departments/DepartmentLevelRequirements',
            compact('departmentLevel', 'institutionDepartment', 'levels', 'requirements'));
    }

    public function updateDepartmentLevelRequirements(DepartmentLevel $departmentLevel, DepartmentLevelRequirementRequest $request): void
    {
        $this->authorize('updateDepartmentMetaData');
        $this->repository->updateDepartmentLevelRequirements($departmentLevel, DepartmentLevelRequirementsDto::fromDepartmentLevelRequirementRequest($request));
    }

    public function syncDepartmentLevels(InstitutionDepartment $institutionDepartment, DepartmentLevelRequest $request): void
    {
        $this->authorize('createDepartmentMetaData');
        $this->repository->syncDepartmentLevels($institutionDepartment, DepartmentLevelDto::fromDepartmentLevelRequest($request));
    }

    public function destroy(DepartmentLevel $departmentLevel)
    {
        $this->authorize('deleteDepartmentMetaData');
        $this->repository->delete($departmentLevel);
    }

    public function restore(string $id)
    {
        $departmentLevel = $this->repository->findTrashed($id);
        $this->authorize('restoreDepartmentMetaData');
        $this->repository->restore($departmentLevel);
    }

    public function forceDelete(DepartmentLevel $departmentLevel)
    {
        $this->authorize('forceDeleteDepartmentMetaData');
        $this->repository->delete($departmentLevel, true);
    }
}
