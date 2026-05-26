<?php

namespace App\Http\Controllers\HMS;

use App\Http\Controllers\Controller;
use App\Http\Requests\HMS\StoreHostelRequest;
use App\Http\Requests\HMS\UpdateHostelRequest;
use App\Models\HMS\Hostel;
use App\Models\Institution\Staff;
use App\Repositories\HMS\interface\IHostelRepository;
use Inertia\Inertia;

class HostelController extends Controller
{
    public function __construct(protected IHostelRepository $repository) {}

    public function index()
    {
        $wardens = Staff::query()
            ->select(['id', 'user_id'])
            ->with(['user:id,first_name,middle_name,last_name'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (Staff $staff) => [
                'id' => $staff->id,
                'name' => $staff->user?->full_name,
            ])
            ->values();

        return Inertia::render('hms/hostels/Index', [
            'wardens' => $wardens,
        ]);
    }

    public function show(Hostel $hostel)
    {
        $hostel->load(['warden.user:id,first_name,middle_name,last_name']);
        $hostel->loadSum('rooms as occupied_beds_sum', 'current_occupancy');

        $wardens = Staff::query()
            ->select(['id', 'user_id'])
            ->with(['user:id,first_name,middle_name,last_name'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (Staff $staff) => [
                'id' => $staff->id,
                'name' => $staff->user?->full_name,
            ])
            ->values();

        return Inertia::render('hms/hostels/Show', [
            'hostel' => $hostel,
            'wardens' => $wardens,
        ]);
    }

    public function store(StoreHostelRequest $request): void
    {
        $this->repository->create($request->validated());
    }

    public function update(UpdateHostelRequest $request, Hostel $hostel): void
    {
        $this->repository->update($hostel, $request->validated());
    }

    public function destroy(Hostel $hostel): void
    {
        $this->repository->delete($hostel);
    }

    public function restore(string $id): void
    {
        $hostel = $this->repository->findTrashed($id);

        $this->repository->restore($hostel);
    }

    public function forceDelete(Hostel $hostel): void
    {
        $this->repository->delete($hostel, true);
    }
}
