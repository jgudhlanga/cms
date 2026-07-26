<?php

namespace App\Http\Controllers\Shared;

use App\DTO\Shared\MaritalStatusDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Http\Requests\Shared\MaritalStatusRequest;
use App\Http\Resources\Shared\MaritalStatusResource;
use App\Models\Shared\MaritalStatus;
use App\Repositories\Shared\interface\IMaritalStatusRepository;
use Inertia\Inertia;

class MaritalStatusController extends Controller
{
    public function __construct(protected IMaritalStatusRepository $repository)
    {
    }

    public function index(SharedTitleFilter $filters)
    {
        $this->authorize('viewAny', MaritalStatus::class);
        $maritalStatuses = MaritalStatusResource::collection($this->repository->allFilter(['*'], $filters));
        return Inertia::render('shared/statuses/maritalStatuses/Index', [
            'maritalStatuses' => $maritalStatuses,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', MaritalStatus::class);
    }

    public function store(MaritalStatusRequest $request)
    {
        $this->authorize('create', MaritalStatus::class);
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
        $this->authorize('update', $maritalStatus);
        $this->repository->update($maritalStatus, MaritalStatusDto::fromMaritalStatusRequest($request));
    }

    public function destroy(MaritalStatus $maritalStatus)
    {
        $this->authorize('delete', $maritalStatus);
        $this->repository->delete($maritalStatus);
    }

    public function restore(string $id)
    {
        $maritalStatus = $this->repository->findTrashed($id);
        $this->authorize('restore', $maritalStatus);
        $this->repository->restore($maritalStatus);
    }

    public function forceDelete(MaritalStatus $maritalStatus)
    {
        $this->authorize('forceDelete', $maritalStatus);
        $this->repository->delete($maritalStatus, true);
    }
}
