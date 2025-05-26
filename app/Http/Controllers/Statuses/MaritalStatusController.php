<?php

namespace App\Http\Controllers\Statuses;

use App\DTO\Statuses\MaritalStatusDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Http\Requests\Statuses\MaritalStatusRequest;
use App\Http\Resources\Statuses\MaritalStatusResource;
use App\Models\Statuses\MaritalStatus;
use App\Repositories\Statuses\interface\IMaritalStatusRepository;
use Inertia\Inertia;

class MaritalStatusController extends Controller
{
    public function __construct(protected IMaritalStatusRepository $repository)
    {
    }

    public function index(SharedTitleFilter $filters)
    {
        $this->authorize('viewSettings');
        $maritalStatuses = MaritalStatusResource::collection($this->repository->allFilter(['*'], $filters));
        return Inertia::render('statuses/maritalStatuses/Index', [
            'maritalStatuses' => $maritalStatuses,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    public function create()
    {
        $this->authorize('createSettings');
    }

    public function store(MaritalStatusRequest $request)
    {
        $this->authorize('createSettings');
        $this->repository->create(MaritalStatusDto::fromMaritalStatusRequest($request));
    }

    public function show(MaritalStatus $maritalStatus)
    {
        //
    }

    public function edit(MaritalStatus $maritalStatus)
    {
        //
    }

    public function update(MaritalStatusRequest $request, MaritalStatus $maritalStatus)
    {
        $this->authorize('updateSettings');
        $this->repository->update($maritalStatus, MaritalStatusDto::fromMaritalStatusRequest($request));
    }

    public function destroy(MaritalStatus $maritalStatus)
    {
        $this->authorize('deleteSettings');
        $this->repository->delete($maritalStatus);
    }

    public function restore(string $id)
    {
        $maritalStatus = $this->repository->findTrashed($id);
        $this->authorize('restoreSettings');
        $this->repository->restore($maritalStatus);
    }

    public function forceDelete(MaritalStatus $maritalStatus)
    {
        $this->authorize('forceDeleteSettings');
        $this->repository->delete($maritalStatus, true);
    }
}
