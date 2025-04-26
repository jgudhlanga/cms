<?php

namespace App\Http\Controllers\Institution;

use App\DTO\Institution\DivisionDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Institution\DivisionRequest;
use App\Http\Resources\Institution\DivisionResource;
use App\Models\Institution\Division;
use App\Repositories\Institution\interface\IDivisionRepository;
use Inertia\Inertia;

class DivisionController extends Controller
{
    public function __construct(protected IDivisionRepository $repository)
    {
    }

    public function index(SharedNameFilter $filters)
    {
        $this->authorize('viewSettings');
        $divisions = DivisionResource::collection($this->repository->allFilter(['*'], $filters));
        return Inertia::render('institution/divisions/Index', [
            'divisions' => $divisions,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    public function create()
    {
        $this->authorize('createSettings');
    }

    public function store(DivisionRequest $request)
    {
        $this->authorize('createSettings');
        $this->repository->create(DivisionDto::fromDivisionRequest($request));
    }

    public function show(Division $division)
    {
        //
    }

    public function edit(Division $division)
    {
        //
    }

    public function update(DivisionRequest $request, Division $division)
    {
        $this->authorize('updateInstitutionSettings');
        $this->repository->update($division, DivisionDto::fromDivisionRequest($request));
    }

    public function destroy(Division $division)
    {
        $this->authorize('deleteInstitutionSettings');
        $this->repository->delete($division);
    }

    public function restore(string $id)
    {
        $division = $this->repository->findTrashed($id);
        $this->authorize('restoreInstitutionSettings');
        $this->repository->restore($division);
    }

    public function forceDelete(Division $division)
    {
        $this->authorize('forceDeleteInstitutionSettings');
        $this->repository->delete($division, true);
    }
}
