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
        $this->authorize('viewAny', WorkflowStep::class);
        $workflowSteps = WorkflowStepResource::collection($this->repository->allFilter(['*'], $filters));
        return Inertia::render('shared/workflowSteps/Index', [
            'workflowSteps' => $workflowSteps,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', WorkflowStep::class);
    }

    public function store(WorkflowStepRequest $request)
    {
        $this->authorize('create', WorkflowStep::class);
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
        $this->authorize('update', $workflowStep);
        $this->repository->update($workflowStep, WorkflowStepDto::fromWorkflowStepRequest($request));
    }

    public function movePosition(PositionRequest $request, WorkflowStep $workflowStep)
    {
        $this->authorize('update', $workflowStep);
        $this->repository->movePosition($workflowStep, $request);
    }

    public function destroy(WorkflowStep $workflowStep)
    {
        $this->authorize('delete', $workflowStep);
        $this->repository->delete($workflowStep);
    }

    public function restore(string $id)
    {
        $workflowStep = $this->repository->findTrashed($id);
        $this->authorize('restore', $workflowStep);
        $this->repository->restore($workflowStep);
    }

    public function forceDelete(WorkflowStep $workflowStep)
    {
        $this->authorize('forceDelete', $workflowStep);
        $this->repository->delete($workflowStep, true);
    }
}
