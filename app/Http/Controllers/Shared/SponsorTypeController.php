<?php

namespace App\Http\Controllers\Shared;

use App\DTO\Shared\SponsorTypeDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Shared\SponsorTypeRequest;
use App\Http\Resources\Shared\SponsorTypeResource;
use App\Models\Shared\SponsorType;
use App\Repositories\Shared\interface\ISponsorTypeRepository;
use Inertia\Inertia;

class SponsorTypeController extends Controller
{
    public function __construct(protected ISponsorTypeRepository $repository)
    {
    }

    public function index(SharedNameFilter $filters)
    {
        $this->authorize('viewSettings');
        $sponsorTypes = SponsorTypeResource::collection($this->repository->allFilter(['*'], $filters));
        return Inertia::render('shared/sponsorTypes/Index', [
            'sponsorTypes' => $sponsorTypes,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    public function create()
    {
        $this->authorize('createSettings');
    }

    public function store(SponsorTypeRequest $request)
    {
        $this->authorize('createSettings');
        $this->repository->create(SponsorTypeDto::fromSponsorTypeRequest($request));
    }

    public function show(SponsorType $sponsorType)
    {
        //
    }

    public function edit(SponsorType $sponsorType)
    {
        //
    }

    public function update(SponsorTypeRequest $request, SponsorType $sponsorType)
    {
        $this->authorize('updateSettings');
        $this->repository->update($sponsorType, SponsorTypeDto::fromSponsorTypeRequest($request));
    }

    public function destroy(SponsorType $sponsorType)
    {
        $this->authorize('deleteSettings');
        $this->repository->delete($sponsorType);
    }

    public function restore(string $id)
    {
        $sponsorType = $this->repository->findTrashed($id);
        $this->authorize('restoreSettings');
        $this->repository->restore($sponsorType);
    }

    public function forceDelete(SponsorType $sponsorType)
    {
        $this->authorize('forceDeleteSettings');
        $this->repository->delete($sponsorType, true);
    }
}
