<?php

namespace App\Http\Controllers\HMS;

use App\Http\Controllers\Controller;
use App\Http\Requests\HMS\StoreHostelAmenityRequest;
use App\Http\Requests\HMS\UpdateHostelAmenityRequest;
use App\Models\HMS\HostelAmenity;
use App\Repositories\HMS\interface\IHostelAmenityRepository;

class HostelAmenityController extends Controller
{
    public function __construct(protected IHostelAmenityRepository $repository) {}

    public function store(StoreHostelAmenityRequest $request): void
    {
        $this->authorize('create', HostelAmenity::class);
        $this->repository->create($request->validated());
    }

    public function update(UpdateHostelAmenityRequest $request, HostelAmenity $hostelAmenity): void
    {
        $this->authorize('update', $hostelAmenity);
        $this->repository->update($hostelAmenity, $request->validated());
    }

    public function destroy(HostelAmenity $hostelAmenity): void
    {
        $this->authorize('delete', $hostelAmenity);
        $this->repository->delete($hostelAmenity);
    }

    public function restore(string $id): void
    {
        $hostelAmenity = $this->repository->findTrashed($id);
        $this->authorize('restore', $hostelAmenity);
        $this->repository->restore($hostelAmenity);
    }

    public function forceDelete(HostelAmenity $hostelAmenity): void
    {
        $this->authorize('forceDelete', $hostelAmenity);
        $this->repository->delete($hostelAmenity, true);
    }
}
