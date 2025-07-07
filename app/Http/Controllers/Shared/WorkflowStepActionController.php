<?php

namespace App\Http\Controllers\Shared;

use App\DTO\Shared\WorkflowStepActionDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Http\Requests\Shared\WorkflowStepActionRequest;
use App\Http\Resources\Shared\WorkflowStepActionResource;
use App\Models\Shared\WorkflowStepAction;
use App\Repositories\Shared\interface\IWorkflowStepActionRepository;
use Inertia\Inertia;

class WorkflowStepActionController extends Controller
{
    public function __construct(protected IWorkflowStepActionRepository $repository)
    {
    }

    public function index(SharedTitleFilter $filters)
    {
        $this->authorize('viewSettings');
        $workflowStepActions = WorkflowStepActionResource::collection($this->repository->allFilter(['*'], $filters));
        return Inertia::render('shared/workflowStepActions/Index', [
            'workflowStepActions' => $workflowStepActions,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    public function create()
    {
        $this->authorize('createSettings');
    }

    public function store(WorkflowStepActionRequest $request)
    {
        $this->authorize('createSettings');
        $this->repository->create(WorkflowStepActionDto::fromWorkflowStepActionRequest($request));
    }

    public function show(WorkflowStepAction $workflowStepAction)
    {
        //
    }

    public function edit(WorkflowStepAction $workflowStepAction)
    {
        //
    }

    public function update(WorkflowStepActionRequest $request, WorkflowStepAction $workflowStepAction)
    {
        $this->authorize('updateSettings');
        $this->repository->update($workflowStepAction, WorkflowStepActionDto::fromWorkflowStepActionRequest($request));
    }

    public function destroy(WorkflowStepAction $workflowStepAction)
    {
        $this->authorize('deleteSettings');
        $this->repository->delete($workflowStepAction);
    }

    public function restore(string $id)
    {
        $workflowStepAction = $this->repository->findTrashed($id);
        $this->authorize('restoreSettings');
        $this->repository->restore($workflowStepAction);
    }

    public function forceDelete(WorkflowStepAction $workflowStepAction)
    {
        $this->authorize('forceDeleteSettings');
        $this->repository->delete($workflowStepAction, true);
    }
}
