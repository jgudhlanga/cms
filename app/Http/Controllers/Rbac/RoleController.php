<?php

namespace App\Http\Controllers\Rbac;

use App\DTO\Rbac\RoleDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Rbac\PermissionFilter;
use App\Http\Requests\Rbac\RoleRequest;
use App\Http\Resources\Rbac\PermissionResource;
use App\Http\Resources\Rbac\RoleResource;
use App\Models\Rbac\Permission;
use App\Models\Rbac\Role;
use App\Repositories\Rbac\Interface\IPermissionRepository;
use App\Repositories\Rbac\Interface\IRoleRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Filters\Rbac\RoleFilter;
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

        return Inertia::render('rbac/roles/Index', [
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

        return Inertia::render('rbac/roles/Show', [
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

    public function syncPermissions(Role $role, Request $request)
    {
        $role->syncPermissions(array_values($request->permissions));
    }
}
