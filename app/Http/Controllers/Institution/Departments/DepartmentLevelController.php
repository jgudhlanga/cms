<?php

namespace App\Http\Controllers\Institution\Departments;

use App\DTO\Institution\DepartmentLevelDto;
use App\DTO\Institution\DepartmentLevelRequirementsDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\DepartmentLevelRequest;
use App\Http\Requests\Institution\DepartmentLevelRequirementRequest;
use App\Http\Resources\Enrolments\EnrolmentResource;
use App\Http\Resources\Institution\DepartmentLevelRequirementResource;
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Models\Institution\DepartmentLevel;
use App\Models\Students\StudentProgram;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Institution\interface\IDepartmentLevelRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response;

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
    public function enrolments(InstitutionDepartment $institutionDepartment, DepartmentLevel $departmentLevel): Response
    {
        $this->authorize('viewAnyDepartmentMetaData');
        $intakePeriodId = request('intake_period');
        $department = InstitutionDepartmentResource::make($institutionDepartment);
        $level = DepartmentLevelResource::make($departmentLevel);
        $enrolments = $institutionDepartment->enrolments()
            ->where('department_level_id', $departmentLevel->id)
            ->when($intakePeriodId, fn($q) => $q->where('intake_period_id', $intakePeriodId))
            ->get();
        $enrolments = EnrolmentResource::collection($enrolments);
        return Inertia::render('institution/enrolments/CourseLevelEnrolments',
            compact('department', 'level', 'enrolments'));
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
}
