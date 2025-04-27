<?php

namespace App\Http\Controllers\Acl;

use App\DTO\Acl\ModuleDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Acl\ModuleFilter;
use App\Http\Requests\Acl\ModuleRequest;
use App\Http\Resources\Acl\ModuleResource;
use App\Models\Acl\Module;
use App\Repositories\Acl\Interface\IModuleRepository;
use Inertia\Inertia;

class ModuleController extends Controller
{
	public function __construct(protected IModuleRepository $repository)
	{
	}

	public function index(ModuleFilter $filters)
	{
		$this->authorize('viewAny', Module::class);
		$modules = ModuleResource::collection($this->repository->allFilter(['*'], $filters));
		return Inertia::render('acl/modules/Index', [
			'modules' => $modules,
			'filters' => request()->only(['search', 'trashed']),
			'trashedCount' => $this->repository->allTrashed()->count(),
		]);
	}

	public function create()
	{
		$this->authorize('create', Module::class);
	}

	public function store(ModuleRequest $request)
	{
		$this->authorize('create', Module::class);
		$this->repository->create(ModuleDto::fromModuleRequest($request));
	}

	public function show(Module $module)
	{
		//
	}

	public function edit(Module $module)
	{
		//
	}

	public function update(ModuleRequest $request, Module $module)
	{
		$this->authorize('create', $module);
		$this->repository->update($module, ModuleDto::fromModuleRequest($request));
	}

	public function destroy(Module $module)
	{
		$this->authorize('delete', $module);
		$this->repository->delete($module);
	}

	public function restore(string $id)
	{
		$module = $this->repository->findTrashed($id);
		$this->authorize('restore', $module);
		$this->repository->restore($module);
	}

	public function forceDelete(Module $module)
	{
		$this->authorize('forceDelete', $module);
		$this->repository->delete($module, true);
	}
}
