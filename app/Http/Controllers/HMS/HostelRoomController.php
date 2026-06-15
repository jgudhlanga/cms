<?php

namespace App\Http\Controllers\HMS;

use App\Http\Controllers\Controller;
use App\Http\Requests\HMS\StoreHostelRoomRequest;
use App\Http\Requests\HMS\UpdateHostelRoomRequest;
use App\Models\HMS\HostelRoom;
use App\Repositories\HMS\interface\IHostelRoomRepository;

class HostelRoomController extends Controller
{
    public function __construct(protected IHostelRoomRepository $repository) {}

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
