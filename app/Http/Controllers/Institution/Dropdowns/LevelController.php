<?php

namespace App\Http\Controllers\Institution\Dropdowns;

use App\DTO\Institution\LevelDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Institution\LevelRequest;
use App\Http\Requests\Shared\PositionRequest;
use App\Http\Resources\Institution\LevelResource;
use App\Models\Institution\Level;
use App\Repositories\Institution\interface\ILevelRepository;
use Inertia\Inertia;

class LevelController extends Controller
{
    public function __construct(protected ILevelRepository $repository)
    {
    }

    public function index(SharedNameFilter $filters)
    {
        $this->authorize('viewSettings');
        $levels = LevelResource::collection($this->repository->allFilter(['*'], $filters));
        return Inertia::render('institution/setup/levels/Index', [
            'levels' => $levels,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    public function create()
    {
        $this->authorize('createSettings');
    }

    public function store(LevelRequest $request)
    {
        $this->authorize('createSettings');
        $this->repository->create(LevelDto::fromLevelRequest($request));
    }

    public function show(Level $level)
    {
        //
    }

    public function edit(Level $level)
    {
        //
    }

    public function update(LevelRequest $request, Level $level)
    {
        $this->authorize('updateInstitutionSettings');
        $this->repository->update($level, LevelDto::fromLevelRequest($request));
    }

    public function movePosition(PositionRequest $request, Level $level): void
    {
        $this->authorize('updateInstitutionSettings');
        $this->repository->movePosition($level, $request);
    }

    public function destroy(Level $level)
    {
        $this->authorize('deleteInstitutionSettings');
        $this->repository->delete($level);
    }

    public function restore(string $id)
    {
        $level = $this->repository->findTrashed($id);
        $this->authorize('restoreInstitutionSettings');
        $this->repository->restore($level);
    }

    public function forceDelete(Level $level)
    {
        $this->authorize('forceDeleteInstitutionSettings');
        $this->repository->delete($level, true);
    }
}
