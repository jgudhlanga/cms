<?php

namespace App\Repositories\HMS\interface;

use App\Models\HMS\HostelRoom;
use App\Repositories\Base\Interface\IBaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;

interface IHostelRoomRepository extends IBaseRepository
{
    public function paginateForIndex(array $filters = []): LengthAwarePaginator;

    public function create(array $data): HostelRoom;

    public function update(HostelRoom $room, array $data): HostelRoom;
}
