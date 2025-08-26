<?php

namespace App\Http\Controllers\Acl;

use App\DTO\Acl\RoleDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Acl\PermissionFilter;
use App\Http\Requests\Acl\RoleRequest;
use App\Http\Resources\Acl\PermissionResource;
use App\Http\Resources\Acl\RoleResource;
use App\Models\Acl\Permission;
use App\Models\Acl\Role;
use App\Repositories\Acl\Interface\IPermissionRepository;
use App\Repositories\Acl\Interface\IRoleRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use App\Http\Filters\Acl\RoleFilter;
use Inertia\Response;

class RoleController extends Controller
{
    public function __construct(protected IRoleRepository $repository, protected IPermissionRepository $permissionRepository)
    {
    }

    /**
     * @throws AuthorizationException
     */
    public function index(RoleFilter $filters): Response
    {
        $this->authorize('viewAny', Role::class);
        $roles = RoleResource::collection($this->repository->allFilter(['*'], $filters));

        return Inertia::render('acl/roles/Index', [
            'roles' => $roles,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function create(): void
    {
        $this->authorize('create', Role::class);
    }

    /**
     * @throws AuthorizationException
     */
    public function store(RoleRequest $request): void
    {
        $this->authorize('create', Role::class);
        $this->repository->create(RoleDto::fromRoleRequest($request));
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Role $role, PermissionFilter $filters): Response
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

    /**
     * @throws AuthorizationException
     */
    public function update(RoleRequest $request, Role $role): void
    {
        $this->authorize('update', $role);
        $this->repository->update($role, RoleDto::fromRoleRequest($request));
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Role $role): void
    {
        $this->authorize('delete', $role);
        $this->repository->delete($role);
    }

    /**
     * @throws AuthorizationException
     */
    public function restore(string $id): void
    {
        $role = $this->repository->findTrashed($id);
        $this->authorize('restore', $role);
        $this->repository->restore($role);
    }

    /**
     * @throws AuthorizationException
     */
    public function forceDelete(Role $role): void
    {
        $this->authorize('forceDelete', $role);
        $this->repository->delete($role, true);
    }
}
