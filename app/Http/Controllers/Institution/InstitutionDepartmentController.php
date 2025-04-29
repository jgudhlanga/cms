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
        $institutionDepartments = InstitutionDepartmentResource::collection($this->repository->allFilter(['*'], $filters));

        return Inertia::render('institution/departments/Index', [
            'institutionDepartments' => $institutionDepartments,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', InstitutionDepartment::class);
    }

    public function store(InstitutionDepartmentRequest $request)
    {
        $this->authorize('create', InstitutionDepartment::class);
        $this->repository->create(InstitutionDepartmentDto::fromInstitutionDepartmentRequest($request));
    }

    public function show(InstitutionDepartment $institutionDepartment)
    {
        $this->authorize('view', $institutionDepartment);
        return Inertia::render('institution/departments/Show', [
            'institutionDepartment' => new InstitutionDepartmentResource($institutionDepartment),
            'filters' => request()->only(['search', 'trashed']),
        ]);
    }

    public function edit(InstitutionDepartment $institutionDepartment)
    {
        //
    }

    public function update(InstitutionDepartmentRequest $request, InstitutionDepartment $institutionDepartment)
    {
        $this->authorize('create', $institutionDepartment);
        $this->repository->update($institutionDepartment, InstitutionDepartmentDto::fromInstitutionDepartmentRequest($request));
    }

    public function destroy(InstitutionDepartment $institutionDepartment)
    {
        $this->authorize('delete', $institutionDepartment);
        $this->repository->delete($institutionDepartment);
    }

    public function restore(string $id)
    {
        $institutionDepartment = $this->repository->findTrashed($id);
        $this->authorize('restore', $institutionDepartment);
        $this->repository->restore($institutionDepartment);
    }

    public function forceDelete(InstitutionDepartment $institutionDepartment)
    {
        $this->authorize('forceDelete', $institutionDepartment);
        $this->repository->delete($institutionDepartment, true);
    }
}
