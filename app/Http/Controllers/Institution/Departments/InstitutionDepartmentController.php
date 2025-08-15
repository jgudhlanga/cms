<?php

namespace App\Http\Controllers\Institution\Departments;

use App\DTO\Institution\InstitutionDepartmentDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Institution\InstitutionDepartmentFilter;
use App\Http\Requests\Institution\InstitutionDepartmentRequest;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Institution\interface\IInstitutionDepartmentRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionDepartmentController extends Controller
{
    public function __construct(protected IInstitutionDepartmentRepository $repository)
    {
    }

    /**
     * @throws AuthorizationException
     */
    public function index(InstitutionDepartmentFilter $filters): Response
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

    /**
     * @throws AuthorizationException
     */
    public function create(): void
    {
        $this->authorize('createDepartmentMetaData');
    }

    /**
     * @throws AuthorizationException
     */
    public function store(InstitutionDepartmentRequest $request): void
    {
        $this->authorize('createDepartmentMetaData');
    }

    /**
     * @throws AuthorizationException
     */
    public function show(InstitutionDepartment $department): Response
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

    /**
     * @throws AuthorizationException
     */
    public function update(InstitutionDepartmentRequest $request, InstitutionDepartment $department): void
    {
        $this->authorize('createDepartmentMetaData');
    }

    /**
     * @throws AuthorizationException
     */
    public function syncInstitutionDepartment(InstitutionDepartmentRequest $request): void
    {
        $this->authorize('createDepartmentMetaData');
        $this->repository->syncInstitutionDepartment(InstitutionDepartmentDto::fromInstitutionDepartmentRequest($request));
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(InstitutionDepartment $department): void
    {
        $this->authorize('deleteDepartmentMetaData');
        $this->repository->delete($department);
    }

    /**
     * @throws AuthorizationException
     */
    public function restore(string $id): void
    {
        $department = $this->repository->findTrashed($id);
        $this->authorize('restoreDepartmentMetaData');
        $this->repository->restore($department);
    }

    /**
     * @throws AuthorizationException
     */
    public function forceDelete(InstitutionDepartment $department): void
    {
        $this->authorize('forceDeleteDepartmentMetaData');
        $this->repository->delete($department, true);
    }
}
