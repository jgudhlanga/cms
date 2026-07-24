<?php

namespace App\Http\Controllers\Rbac;

use App\DTO\Rbac\ModuleDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Rbac\ModuleFilter;
use App\Http\Requests\Rbac\ModuleRequest;
use App\Http\Requests\Rbac\ModuleSettingsRequest;
use App\Http\Requests\Rbac\ModuleStatusRequest;
use App\Http\Resources\Rbac\ModuleResource;
use App\Models\Rbac\Module;
use App\Repositories\Rbac\Interface\IModuleRepository;
use App\Services\Rbac\RbacModuleStateService;
use App\Services\Dashboard\DashboardModuleService;
use Inertia\Inertia;

class ModuleController extends Controller
{
    public function __construct(
        protected IModuleRepository $repository,
        protected DashboardModuleService $dashboardModuleService,
        protected RbacModuleStateService $rbacModuleStateService,
    ) {}

    public function index(ModuleFilter $filters)
    {
        $this->authorize('viewAny', Module::class);
        $modules = ModuleResource::collection($this->repository->allFilter(['*'], $filters));

        return Inertia::render('rbac/modules/Index', [
            'modules' => $modules,
            'moduleState' => $this->rbacModuleStateService->all(),
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

        return Inertia::render('rbac/modules/Show', [
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

        $this->rbacModuleStateService->clearCache();

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

        $this->rbacModuleStateService->clearCache();

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
