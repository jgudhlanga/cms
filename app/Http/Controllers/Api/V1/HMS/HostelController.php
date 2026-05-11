<?php

namespace App\Http\Controllers\Api\V1\HMS;

use App\Http\Controllers\Controller;
use App\Repositories\HMS\interface\IHostelRepository;
use App\Http\Resources\HMS\HostelResource;
class HostelController extends Controller
{
     public function __construct(protected IHostelRepository $repository) {}

    public function index()
    {
        $hostels = $this->repository->paginateForIndex(request()->only(['search', 'type', 'warden', 'with_trashed']));
        return HostelResource::collection($hostels);//
    }
}
