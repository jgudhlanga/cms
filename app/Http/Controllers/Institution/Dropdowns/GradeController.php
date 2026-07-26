<?php

namespace App\Http\Controllers\Institution\Dropdowns;

use App\DTO\Institution\GradeDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Institution\GradeRequest;
use App\Http\Requests\Shared\PositionRequest;
use App\Http\Resources\Institution\GradeResource;
use App\Models\Institution\Grade;
use App\Repositories\Institution\interface\IGradeRepository;
use Inertia\Inertia;

class GradeController extends Controller
{
    public function __construct(protected IGradeRepository $repository)
    {
    }

    public function index(SharedNameFilter $filters)
    {
        $this->authorize('viewAny', Grade::class);
        $grades = GradeResource::collection($this->repository->allFilter(['*'], $filters));
        return Inertia::render('institution/dropdowns/grades/Index', [
            'grades' => $grades,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Grade::class);
    }

    public function store(GradeRequest $request)
    {
        $this->authorize('create', Grade::class);
        $this->repository->create(GradeDto::fromGradeRequest($request));
    }

    public function show(Grade $grade)
    {
        //
    }

    public function edit(Grade $grade)
    {
        //
    }

    public function update(GradeRequest $request, Grade $grade)
    {
        $this->authorize('update', $grade);
        $this->repository->update($grade, GradeDto::fromGradeRequest($request));
    }

    public function movePosition(PositionRequest $request, Grade $grade)
    {
        $this->authorize('update', $grade);
        $this->repository->movePosition($grade, $request);
    }

    public function destroy(Grade $grade)
    {
        $this->authorize('delete', $grade);
        $this->repository->delete($grade);
    }

    public function restore(string $id)
    {
        $grade = $this->repository->findTrashed($id);
        $this->authorize('restore', $grade);
        $this->repository->restore($grade);
    }

    public function forceDelete(Grade $grade)
    {
        $this->authorize('forceDelete', $grade);
        $this->repository->delete($grade, true);
    }
}
