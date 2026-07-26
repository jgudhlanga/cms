<?php

namespace App\Http\Controllers\Rbac;

use App\DTO\Rbac\RoleGroupDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Rbac\RoleGroupRequest;
use App\Http\Resources\Rbac\RoleGroupResource;
use App\Models\Rbac\Role;
use App\Models\Rbac\RoleGroup;
use App\Repositories\Rbac\Interface\IRoleGroupRepository;
use Inertia\Inertia;

class RoleGroupController extends Controller
{
    public function __construct(protected IRoleGroupRepository $repository)
    {
    }

    public function index(SharedNameFilter $filters)
    {
        $this->authorize('viewAny', Role::class);
        $roleGroups = RoleGroupResource::collection($this->repository->allFilter(['*'], $filters));
        return Inertia::render('rbac/roleGroups/Index', [
            'roleGroups' => $roleGroups,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Role::class);
    }

    public function store(RoleGroupRequest $request)
    {
        $this->authorize('create', Role::class);
        $this->repository->create(RoleGroupDto::fromRoleGroupRequest($request));
    }

    public function show(RoleGroup $roleGroup)
    {
        //
    }

    public function edit(RoleGroup $roleGroup)
    {
        //
    }

    public function update(RoleGroup $roleGroup, RoleGroupRequest $request)
    {
        abort_unless($request->user()?->can('update:roles'), 403);
        $this->repository->update($roleGroup, RoleGroupDto::fromRoleGroupRequest($request));
    }


    public function destroy(RoleGroup $roleGroup)
    {
        abort_unless(request()->user()?->can('delete:roles'), 403);
        $this->repository->delete($roleGroup);
    }

    public function restore(string $id)
    {
        abort_unless(request()->user()?->can('restore:roles'), 403);
        $roleGroup = $this->repository->findTrashed($id);
        $this->repository->restore($roleGroup);
    }

    public function forceDelete(RoleGroup $roleGroup)
    {
        abort_unless(request()->user()?->can('forceDelete:roles'), 403);
        $this->repository->delete($roleGroup, true);
    }
}
