<?php

namespace App\Http\Controllers\Institution\Dropdowns;

use App\DTO\Institution\DepartmentDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Institution\DepartmentRequest;
use App\Http\Resources\Institution\DepartmentResource;
use App\Models\Institution\Department;
use App\Repositories\Institution\interface\IDepartmentRepository;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function __construct(protected IDepartmentRepository $repository)
    {
    }

    public function index(SharedNameFilter $filters)
    {
        $this->authorize('viewSettings');
        $departments = DepartmentResource::collection($this->repository->allFilter(['*'], $filters));
        return Inertia::render('institution/setup/departments/Index', [
            'departments' => $departments,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    public function create()
    {
        $this->authorize('createSettings');
    }

    public function store(DepartmentRequest $request)
    {
        $this->authorize('createSettings');
        $this->repository->create(DepartmentDto::fromDepartmentRequest($request));
    }

    public function show(Department $department)
    {
        //
    }

    public function edit(Department $department)
    {
        //
    }

    public function update(DepartmentRequest $request, Department $department)
    {
        $this->authorize('updateInstitutionSettings');
        $this->repository->update($department, DepartmentDto::fromDepartmentRequest($request));
    }

    public function destroy(Department $department)
    {
        $this->authorize('deleteInstitutionSettings');
        $this->repository->delete($department);
    }

    public function restore(string $id)
    {
        $department = $this->repository->findTrashed($id);
        $this->authorize('restoreInstitutionSettings');
        $this->repository->restore($department);
    }

    public function forceDelete(Department $department)
    {
        $this->authorize('forceDeleteInstitutionSettings');
        $this->repository->delete($department, true);
    }
}
