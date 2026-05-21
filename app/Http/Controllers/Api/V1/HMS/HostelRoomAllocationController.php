<?php

namespace App\Http\Controllers\Api\V1\HMS;

use App\Http\Controllers\Controller;
use App\Http\Resources\HMS\HostelRoomAllocationResource;
use App\Repositories\HMS\interface\IHostelRoomAllocationRepository;

class HostelRoomAllocationController extends Controller
{
    public function __construct(protected IHostelRoomAllocationRepository $repository) {}

    public function index()
    {
        $allocations = $this->repository->paginateForIndex(
            request()->only([
                'search',
                'name',
                'gender',
                'hostel',
                'room',
                'type',
                'status',
                'with_trashed',
            ])
        );

        return HostelRoomAllocationResource::collection($allocations);
    }
}
