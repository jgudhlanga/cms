<?php

namespace App\Http\Controllers\Institution;

use App\DTO\Institution\DepartmentLevelDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\DepartmentLevelRequest;
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Institution\interface\IDepartmentLevelRepository;
use Inertia\Inertia;
use phpDocumentor\Reflection\Types\Compound;

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
        return Inertia::render('institution/departments/DepartmentLevelRequirements', compact('departmentLevel', 'institutionDepartment'));
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
