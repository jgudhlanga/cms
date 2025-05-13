<?php

namespace App\Http\Controllers\Institution;

use App\DTO\Institution\InstitutionDepartmentDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Institution\DepartmentFilter;
use App\Http\Requests\Institution\InstitutionDepartmentRequest;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Institution\interface\IInstitutionDepartmentRepository;
use Inertia\Inertia;

class InstitutionDepartmentController extends Controller
{
    public function __construct(protected IInstitutionDepartmentRepository $repository)
    {
    }

    public function index(DepartmentFilter $filters)
    {
        $this->authorize('viewAnyDepartmentMetaData');
        $departments = InstitutionDepartmentResource::collection($this->repository->allFilter(['*'], $filters));
        $institutionDepartmentIds = InstitutionDepartment::all()->pluck('id');
        return Inertia::render('institution/departments/Index', [
            'departments' => $departments,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
            'institutionDepartmentIds' => $institutionDepartmentIds,
        ]);
    }

    public function create()
    {
        $this->authorize('createDepartmentMetaData');
    }

    public function store(InstitutionDepartmentRequest $request)
    {
        $this->authorize('createDepartmentMetaData');
    }

    public function show(InstitutionDepartment $department)
    {
        $this->authorize('viewDepartmentMetaData');
        return Inertia::render('institution/departments/Show', [
            'department' => new InstitutionDepartmentResource($department),
        ]);
    }

    public function edit(InstitutionDepartment $department)
    {
        //
    }

    public function update(InstitutionDepartmentRequest $request, InstitutionDepartment $department)
    {
        $this->authorize('createDepartmentMetaData');
    }

    public function syncInstitutionDepartment(InstitutionDepartmentRequest $request): void
    {
        $this->authorize('createDepartmentMetaData');
        $this->repository->syncInstitutionDepartment(InstitutionDepartmentDto::fromInstitutionDepartmentRequest($request));
    }

    public function destroy(InstitutionDepartment $department)
    {
        $this->authorize('deleteDepartmentMetaData');
        $this->repository->delete($department);
    }

    public function restore(string $id)
    {
        $department = $this->repository->findTrashed($id);
        $this->authorize('restoreDepartmentMetaData');
        $this->repository->restore($department);
    }

    public function forceDelete(InstitutionDepartment $department)
    {
        $this->authorize('forceDeleteDepartmentMetaData');
        $this->repository->delete($department, true);
    }
}
