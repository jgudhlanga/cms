<?php

namespace App\Repositories\HMS\interface;

use App\Models\HMS\HostelRoom;
use App\Repositories\Base\Interface\IBaseRepository;

interface IHostelRoomRepository extends IBaseRepository
{
    /**
     * @return array{total_rooms: int, total_capacity: int, total_max_occupancy: int, vacant_count: int}
     */
    public function statsForIndex(): array;

    public function create(array $data): HostelRoom;

    public function update(HostelRoom $room, array $data): HostelRoom;
}
