<?php

namespace App\Repositories\HMS\interface;

use App\Repositories\Base\Interface\IBaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;

interface IHostelRoomAllocationRepository extends IBaseRepository
{
    public function paginateForIndex(array $filters = []): LengthAwarePaginator;
}
