<?php

namespace App\Http\Controllers\Acl;

use App\DTO\Acl\RoleDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Acl\PermissionFilter;
use App\Http\Filters\Acl\RoleFilter;
use App\Http\Requests\Acl\RoleRequest;
use App\Http\Resources\Acl\PermissionResource;
use App\Http\Resources\Acl\RoleResource;
use App\Models\Acl\Permission;
use App\Models\Acl\Role;
use App\Repositories\Acl\Interface\IPermissionRepository;
use App\Repositories\Acl\Interface\IRoleRepository;
use Inertia\Inertia;

class RoleController extends Controller
{
    public function __construct(protected IRoleRepository $repository, protected IPermissionRepository $permissionRepository)
    {
    }

    public function index(RoleFilter $filters)
    {
        $this->authorize('viewAny', Role::class);
        $roles = RoleResource::collection($this->repository->allFilter(['*'], $filters));

        return Inertia::render('acl/roles/Index', [
            'roles' => $roles,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Role::class);
    }

    public function store(RoleRequest $request)
    {
        $this->authorize('create', Role::class);
        $this->repository->create(RoleDto::fromRoleRequest($request));
    }

    public function show(Role $role, PermissionFilter $filters)
    {
        $this->authorize('view', $role);
        $permissions = PermissionResource::collection($this->permissionRepository->allFilter(['*'], $filters));
        $allPermissions = PermissionResource::collection(Permission::all());

        return Inertia::render('acl/roles/Show', [
            'role' => new RoleResource($role),
            'permissions' => $permissions,
            'allPermissions' => $allPermissions,
            'filters' => request()->only(['search', 'trashed']),
        ]);
    }

    public function edit(Role $role)
    {
        //
    }

    public function update(RoleRequest $request, Role $role)
    {
        $this->authorize('create', $role);
        $this->repository->update($role, RoleDto::fromRoleRequest($request));
    }

    public function destroy(Role $role)
    {
        $this->authorize('delete', $role);
        $this->repository->delete($role);
    }

    public function restore(string $id)
    {
        $role = $this->repository->findTrashed($id);
        $this->authorize('restore', $role);
        $this->repository->restore($role);
    }

    public function forceDelete(Role $role)
    {
        $this->authorize('forceDelete', $role);
        $this->repository->delete($role, true);
    }
}
