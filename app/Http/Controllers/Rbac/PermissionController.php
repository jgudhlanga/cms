<?php

namespace App\Http\Controllers\Rbac;

use App\DTO\Rbac\PermissionDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Rbac\PermissionFilter;
use App\Http\Requests\Rbac\PermissionRequest;
use App\Http\Resources\Rbac\PermissionResource;
use App\Models\Rbac\Permission;
use App\Repositories\Rbac\Interface\IPermissionRepository;
use Inertia\Inertia;

class PermissionController extends Controller
{
	public function __construct(protected IPermissionRepository $repository)
	{
	}

	public function index(PermissionFilter $filters)
	{
		$this->authorize('viewAny', Permission::class);
		$permissions = PermissionResource::collection($this->repository->allFilter(['*'], $filters));
		return Inertia::render('rbac/permissions/Index', [
			'permissions' => $permissions,
			'filters' => request()->only(['search', 'trashed']),
			'trashedCount' => $this->repository->allTrashed()->count(),
		]);
	}

	public function create()
	{
		$this->authorize('create', Permission::class);
	}

	public function store(PermissionRequest $request)
	{
		$this->authorize('create', Permission::class);
		$this->repository->create(PermissionDto::fromPermissionRequest($request));
	}

	public function show(Permission $permission)
	{
		//
	}

	public function edit(Permission $permission)
	{
		//
	}

	public function update(PermissionRequest $request, Permission $permission)
	{
		$this->authorize('create', $permission);
		$this->repository->update($permission, PermissionDto::fromPermissionRequest($request));
	}

	public function destroy(Permission $permission)
	{
		$this->authorize('delete', $permission);
		$this->repository->delete($permission);
	}

	public function restore(string $id)
	{
		$permission = $this->repository->findTrashed($id);
		$this->authorize('restore', $permission);
		$this->repository->restore($permission);
	}

	public function forceDelete(Permission $permission)
	{
		$this->authorize('forceDelete', $permission);
		$this->repository->delete($permission, true);
	}
}
