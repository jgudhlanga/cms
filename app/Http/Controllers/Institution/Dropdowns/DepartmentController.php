<?php

namespace App\Http\Controllers\Institution\Dropdowns;

use App\DTO\Institution\DepartmentDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Institution\DepartmentFilter;
use App\Http\Requests\Institution\DepartmentRequest;
use App\Http\Requests\Shared\PositionRequest;
use App\Http\Resources\Institution\DepartmentResource;
use App\Models\Institution\Department;
use App\Repositories\Institution\interface\IDepartmentRepository;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function __construct(protected IDepartmentRepository $repository)
    {
    }

    public function index(DepartmentFilter $filters)
    {
        $this->authorize('viewAny', Department::class);
        $departments = DepartmentResource::collection($this->repository->allFilter(['*'], $filters));
        return Inertia::render('institution/dropdowns/departments/Index', [
            'departments' => $departments,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Department::class);
    }

    public function store(DepartmentRequest $request)
    {
        $this->authorize('create', Department::class);
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
        $this->authorize('update', $department);
        $this->repository->update($department, DepartmentDto::fromDepartmentRequest($request));
    }

    public function movePosition(PositionRequest $request, Department $department)
    {
        $this->authorize('update', $department);
        $this->repository->movePosition($department, $request);
    }

    public function destroy(Department $department)
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

    public function forceDelete(Department $department)
    {
        $this->authorize('forceDelete', $department);
        $this->repository->delete($department, true);
    }
}
