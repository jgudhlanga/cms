<?php

namespace App\Http\Controllers\Api\V1\HMS;

use App\Http\Controllers\Controller;
use App\Repositories\HMS\interface\IHostelRoomRepository;

class HostelRoomController extends Controller
{
    public function __construct(protected IHostelRoomRepository $repository) {}

    public function stats()
    {
        return response()->json($this->repository->statsForIndex());
    }
}
