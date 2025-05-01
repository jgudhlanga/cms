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
        $this->authorize('viewAny', InstitutionDepartment::class);
        $departments = InstitutionDepartmentResource::collection($this->repository->allFilter(['*'], $filters));
        $allInstitutionDepartmentIds = InstitutionDepartment::all()->pluck('id');
        return Inertia::render('institution/departments/Index', [
            'departments' => $departments,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
            'allInstitutionDepartmentIds' => $allInstitutionDepartmentIds,
        ]);
    }

    public function create()
    {
        $this->authorize('create', InstitutionDepartment::class);
    }

    public function store(InstitutionDepartmentRequest $request)
    {
        $this->authorize('create', InstitutionDepartment::class);
    }

    public function show(InstitutionDepartment $department)
    {
        $this->authorize('view', $department);
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
        $this->authorize('create', $department);
    }

    public function syncInstitutionDepartment(InstitutionDepartmentRequest $request): void
    {
        $this->authorize('create', InstitutionDepartment::class);
        $this->repository->syncInstitutionDepartment(InstitutionDepartmentDto::fromInstitutionDepartmentRequest($request));
    }

    public function destroy(InstitutionDepartment $department)
    {
        $this->authorize('delete', $department);
        $this->repository->delete($department);
    }

    public function restore(string $id)
    {
        $department = $this->repository->findTrashed($id);
        $this->authorize('restore', $department);
        $this->repository->restore($department);
    }

    public function forceDelete(InstitutionDepartment $department)
    {
        $this->authorize('forceDelete', $department);
        $this->repository->delete($department, true);
    }
}
