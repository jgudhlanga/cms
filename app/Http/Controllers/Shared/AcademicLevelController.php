<?php

namespace App\Http\Controllers\Shared;

use App\DTO\AcademicLevels\AcademicLevelDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Shared\AcademicLevelRequest;
use App\Http\Resources\Shared\AcademicLevelResource;
use App\Models\Institution\Level;
use App\Models\Shared\AcademicLevel;
use App\Repositories\Shared\interface\IAcademicLevelRepository;
use Inertia\Inertia;

class AcademicLevelController extends Controller
{
    public function __construct(protected IAcademicLevelRepository $repository)
    {
    }

    public function index(SharedNameFilter $filters)
    {
        $this->authorize('viewSettings');
        $academicLevels = AcademicLevelResource::collection($this->repository->allFilter(['*'], $filters));
        return Inertia::render('academicLevels/Index', [
            'academicLevels' => $academicLevels,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    public function create()
    {
        $this->authorize('createSettings');
    }

    public function store(AcademicLevelRequest $request)
    {
        $this->authorize('createSettings');
        $this->repository->create(AcademicLevelDto::fromAcademicLevelRequest($request));
    }

    public function show(Level $level)
    {
        //
    }

    public function edit(Level $level)
    {
        //
    }

    public function update(AcademicLevelRequest $request, AcademicLevel $academicLevel)
    {
        $this->authorize('updateInstitutionSettings');
        $this->repository->update($academicLevel, AcademicLevelDto::fromAcademicLevelRequest($request));
    }

    public function destroy(AcademicLevel $academicLevel)
    {
        $this->authorize('deleteInstitutionSettings');
        $this->repository->delete($academicLevel);
    }

    public function restore(string $id)
    {
        $academicLevel = $this->repository->findTrashed($id);
        $this->authorize('restoreInstitutionSettings');
        $this->repository->restore($academicLevel);
    }

    public function forceDelete(AcademicLevel $academicLevel)
    {
        $this->authorize('forceDeleteInstitutionSettings');
        $this->repository->delete($academicLevel, true);
    }
}
