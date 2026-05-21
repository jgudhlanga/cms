<?php

namespace App\Http\Controllers\Api\V1\HMS;

use App\Http\Controllers\Controller;
use App\Http\Resources\HMS\HostelRoomResource;
use App\Repositories\HMS\interface\IHostelRoomRepository;

class HostelRoomController extends Controller
{
    public function __construct(protected IHostelRoomRepository $repository) {}

    public function index()
    {
        $rooms = $this->repository->paginateForIndex(
            request()->only(['search', 'hostel', 'with_trashed'])
        );

        return HostelRoomResource::collection($rooms);
    }

    public function stats()
    {
        return response()->json($this->repository->statsForIndex());
    }
}
