<?php

namespace App\Http\Controllers\Institution\Dropdowns;

use App\DTO\Institution\ModeOfStudyDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Institution\ModeOfStudyRequest;
use App\Http\Resources\Institution\ModeOfStudyResource;
use App\Models\Institution\ModeOfStudy;
use App\Repositories\Institution\interface\IModeOfStudyRepository;
use Inertia\Inertia;

class ModeOfStudyController extends Controller
{
    public function __construct(protected IModeOfStudyRepository $repository)
    {
    }

    public function index(SharedNameFilter $filters)
    {
        $this->authorize('viewAny', ModeOfStudy::class);
        $modesOfStudy = ModeOfStudyResource::collection($this->repository->allFilter(['*'], $filters));
        return Inertia::render('institution/dropdowns/modesOfStudy/Index', [
            'modesOfStudy' => $modesOfStudy,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', ModeOfStudy::class);
    }

    public function store(ModeOfStudyRequest $request)
    {
        $this->authorize('create', ModeOfStudy::class);
        $this->repository->create(ModeOfStudyDto::fromModeOfStudyRequest($request));
    }

    public function show(ModeOfStudy $modeOfStudy)
    {
        //
    }

    public function edit(ModeOfStudy $modeOfStudy)
    {
        //
    }

    public function update(ModeOfStudyRequest $request, ModeOfStudy $modeOfStudy)
    {
        $this->authorize('update', $modeOfStudy);
        $this->repository->update($modeOfStudy, ModeOfStudyDto::fromModeOfStudyRequest($request));
    }

    public function destroy(ModeOfStudy $modeOfStudy)
    {
        $this->authorize('delete', $modeOfStudy);
        $this->repository->delete($modeOfStudy);
    }

    public function restore(string $id)
    {
        $modeOfStudy = $this->repository->findTrashed($id);
        $this->authorize('restore', $modeOfStudy);
        $this->repository->restore($modeOfStudy);
    }

    public function forceDelete(ModeOfStudy $modeOfStudy)
    {
        $this->authorize('forceDelete', $modeOfStudy);
        $this->repository->delete($modeOfStudy, true);
    }
}
