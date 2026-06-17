<?php

namespace App\Http\Controllers\Acl;

use App\DTO\Acl\ModuleDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Acl\ModuleFilter;
use App\Http\Requests\Acl\ModuleRequest;
use App\Http\Requests\Acl\ModuleSettingsRequest;
use App\Http\Requests\Acl\ModuleStatusRequest;
use App\Http\Resources\Acl\ModuleResource;
use App\Models\Acl\Module;
use App\Repositories\Acl\Interface\IModuleRepository;
use App\Services\Acl\AclModuleStateService;
use App\Services\Dashboard\DashboardModuleService;
use Inertia\Inertia;

class ModuleController extends Controller
{
    public function __construct(
        protected IModuleRepository $repository,
        protected DashboardModuleService $dashboardModuleService,
        protected AclModuleStateService $aclModuleStateService,
    ) {}

    public function index(ModuleFilter $filters)
    {
        $this->authorize('viewAny', Module::class);
        $modules = ModuleResource::collection($this->repository->allFilter(['*'], $filters));

        return Inertia::render('acl/modules/Index', [
            'modules' => $modules,
            'moduleState' => $this->aclModuleStateService->all(),
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
        $this->authorize('view', $module);

        return Inertia::render('acl/modules/Show', [
            'module' => ModuleResource::make($module),
        ]);
    }

    public function edit(Module $module)
    {
        //
    }

    public function update(ModuleRequest $request, Module $module)
    {
        $this->authorize('update', $module);
        $this->repository->update($module, ModuleDto::fromModuleRequest($request));
    }

    public function updateSettings(ModuleSettingsRequest $request, Module $module)
    {
        $this->authorize('update', $module);

        $settings = $module->slug === 'dashboards'
            ? $request->validated('settings')
            : null;

        $this->repository->updateSettings(
            $module,
            $request->boolean('status'),
            $settings,
        );

        $this->aclModuleStateService->clearCache();

        return redirect()
            ->route('modules.show', $module)
            ->with('success', __('trans.item_saved', ['item' => __('trans.module')]));
    }

    public function updateStatus(ModuleStatusRequest $request, Module $module)
    {
        $this->authorize('update', $module);

        $this->repository->updateSettings(
            $module,
            $request->boolean('status'),
        );

        $this->aclModuleStateService->clearCache();

        return back();
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
