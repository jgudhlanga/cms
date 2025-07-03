<?php

namespace App\Http\Controllers\Shared;

use App\DTO\Shared\ApplicationStepDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Shared\ApplicationStepRequest;
use App\Http\Requests\Shared\PositionRequest;
use App\Http\Resources\Shared\ApplicationStepResource;
use App\Models\Shared\ApplicationStep;
use App\Repositories\Shared\interface\IApplicationStepRepository;
use Inertia\Inertia;

class ApplicationStepController extends Controller
{
    public function __construct(protected IApplicationStepRepository $repository)
    {
    }

    public function index(SharedNameFilter $filters)
    {
        $this->authorize('viewSettings');
        $applicationSteps = ApplicationStepResource::collection($this->repository->allFilter(['*'], $filters));
        return Inertia::render('shared/applicationSteps/Index', [
            'applicationSteps' => $applicationSteps,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    public function create()
    {
        $this->authorize('createSettings');
    }

    public function store(ApplicationStepRequest $request)
    {
        $this->authorize('createSettings');
        $this->repository->create(ApplicationStepDto::fromApplicationStepRequest($request));
    }

    public function show(ApplicationStep $applicationStep)
    {
        //
    }

    public function edit(ApplicationStep $applicationStep)
    {
        //
    }

    public function update(ApplicationStepRequest $request, ApplicationStep $applicationStep)
    {
        $this->authorize('updateSettings');
        $this->repository->update($applicationStep, ApplicationStepDto::fromApplicationStepRequest($request));
    }

    public function movePosition(PositionRequest $request, ApplicationStep $applicationStep)
    {
        $this->authorize('updateSettings');
        $this->repository->movePosition($applicationStep, $request);
    }

    public function destroy(ApplicationStep $applicationStep)
    {
        $this->authorize('deleteSettings');
        $this->repository->delete($applicationStep);
    }

    public function restore(string $id)
    {
        $applicationStep = $this->repository->findTrashed($id);
        $this->authorize('restoreSettings');
        $this->repository->restore($applicationStep);
    }

    public function forceDelete(ApplicationStep $applicationStep)
    {
        $this->authorize('forceDeleteSettings');
        $this->repository->delete($applicationStep, true);
    }
}
