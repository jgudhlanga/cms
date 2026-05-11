<?php

namespace App\Http\Controllers\HMS;

use App\Http\Controllers\Controller;
use App\Http\Requests\HMS\StoreHostelRoomRequest;
use App\Http\Requests\HMS\UpdateHostelRoomRequest;
use App\Models\HMS\Hostel;
use App\Models\HMS\HostelRoom;
use App\Repositories\HMS\interface\IHostelRoomRepository;
use Inertia\Inertia;

class HostelRoomController extends Controller
{
    public function __construct(protected IHostelRoomRepository $repository) {}

    public function index()
    {
        $hostels = Hostel::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn(Hostel $h) => ['id' => $h->id, 'name' => $h->name])
            ->values();

        return Inertia::render('hms/hostels/Index', [
            'hostels' => $hostels,
        ]);
    }

    public function store(StoreHostelRoomRequest $request): void
    {
        $this->repository->create($request->validated());
    }

    public function update(UpdateHostelRoomRequest $request, HostelRoom $hostelRoom): void
    {
        $this->repository->update($hostelRoom, $request->validated());
    }

    public function destroy(HostelRoom $hostelRoom): void
    {
        $this->repository->delete($hostelRoom);
    }

    public function restore(string $id): void
    {
        $room = $this->repository->findTrashed($id);
        $this->repository->restore($room);
    }

    public function forceDelete(HostelRoom $hostelRoom): void
    {
        $this->repository->delete($hostelRoom, true);
    }
}
