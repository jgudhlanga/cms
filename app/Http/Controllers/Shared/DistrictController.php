<?php

namespace App\Http\Controllers\Shared;

use App\DTO\Shared\DistrictDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Shared\DistrictRequest;
use App\Http\Resources\Shared\DistrictResource;
use App\Models\Shared\District;
use App\Repositories\Shared\interface\IDistrictRepository;
use Inertia\Inertia;

class DistrictController extends Controller
{
    public function __construct(protected IDistrictRepository $repository)
    {
    }

    public function index(SharedNameFilter $filters)
    {
        $this->authorize('viewSettings');
        $districts = DistrictResource::collection($this->repository->allFilter(['*'], $filters));
        return Inertia::render('shared/districts/Index', [
            'districts' => $districts,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    public function create()
    {
        $this->authorize('createSettings');
    }

    public function store(DistrictRequest $request)
    {
        $this->authorize('createSettings');
        $this->repository->create(DistrictDto::fromDistrictRequest($request));
    }

    public function show(District $district)
    {
        //
    }

    public function edit(District $district)
    {
        //
    }

    public function update(DistrictRequest $request, District $district)
    {
        $this->authorize('updateSettings');
        $this->repository->update($district, DistrictDto::fromDistrictRequest($request));
    }

    public function destroy(District $district)
    {
        $this->authorize('deleteSettings');
        $this->repository->delete($district);
    }

    public function restore(string $id)
    {
        $district = $this->repository->findTrashed($id);
        $this->authorize('restoreSettings');
        $this->repository->restore($district);
    }

    public function forceDelete(District $district)
    {
        $this->authorize('forceDeleteSettings');
        $this->repository->delete($district, true);
    }
}
