<?php

namespace App\Http\Controllers\Shared;

use App\DTO\Shared\WorkflowStepDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Shared\WorkflowStepRequest;
use App\Http\Requests\Shared\PositionRequest;
use App\Http\Resources\Shared\WorkflowStepResource;
use App\Models\Shared\WorkflowStep;
use App\Repositories\Shared\interface\IWorkflowStepRepository;
use Inertia\Inertia;

class WorkflowStepController extends Controller
{
    public function __construct(protected IWorkflowStepRepository $repository)
    {
    }

    public function index(SharedNameFilter $filters)
    {
        $this->authorize('viewSettings');
        $workflowSteps = WorkflowStepResource::collection($this->repository->allFilter(['*'], $filters));
        return Inertia::render('shared/workflowSteps/Index', [
            'workflowSteps' => $workflowSteps,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    public function create()
    {
        $this->authorize('createSettings');
    }

    public function store(WorkflowStepRequest $request)
    {
        $this->authorize('createSettings');
        $this->repository->create(WorkflowStepDto::fromWorkflowStepRequest($request));
    }

    public function show(WorkflowStep $workflowStep)
    {
        //
    }

    public function edit(WorkflowStep $workflowStep)
    {
        //
    }

    public function update(WorkflowStepRequest $request, WorkflowStep $workflowStep)
    {
        $this->authorize('updateSettings');
        $this->repository->update($workflowStep, WorkflowStepDto::fromWorkflowStepRequest($request));
    }

    public function movePosition(PositionRequest $request, WorkflowStep $workflowStep)
    {
        $this->authorize('updateSettings');
        $this->repository->movePosition($workflowStep, $request);
    }

    public function destroy(WorkflowStep $workflowStep)
    {
        $this->authorize('deleteSettings');
        $this->repository->delete($workflowStep);
    }

    public function restore(string $id)
    {
        $workflowStep = $this->repository->findTrashed($id);
        $this->authorize('restoreSettings');
        $this->repository->restore($workflowStep);
    }

    public function forceDelete(WorkflowStep $workflowStep)
    {
        $this->authorize('forceDeleteSettings');
        $this->repository->delete($workflowStep, true);
    }
}
