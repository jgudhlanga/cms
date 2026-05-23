<?php

namespace App\Http\Controllers\Api\V1\HMS;

use App\Repositories\HMS\interface\IHostelRoomRepository;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Responses\MetaResponse;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;

class HostelRoomController extends JsonApiController
{
    public function __construct(protected IHostelRoomRepository $repository) {}

    public function stats(Request $request): MetaResponse
    {
        abort_unless($request->user() !== null, 403);

        $stats = $this->repository->statsForIndex();

        return MetaResponse::make([
            'totalRooms' => $stats['total_rooms'],
            'totalCapacity' => $stats['total_capacity'],
            'totalMaxOccupancy' => $stats['total_max_occupancy'],
            'vacantCount' => $stats['vacant_count'],
        ]);
    }
}
