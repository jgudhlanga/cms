<?php

namespace App\Repositories\HMS;

use App\Models\HMS\HostelRoom;
use App\Repositories\Base\BaseRepository;
use App\Repositories\HMS\interface\IHostelRoomRepository;

class HostelRoomRepository extends BaseRepository implements IHostelRoomRepository
{
    public function __construct(protected HostelRoom $room)
    {
        parent::__construct($this->room);
    }

    public function statsForIndex(): array
    {
        $stats = $this->room->query()
            ->selectRaw('count(*) as total_rooms')
            ->selectRaw('coalesce(sum(capacity), 0) as total_capacity')
            ->selectRaw('coalesce(sum(max_occupancy), 0) as total_max_occupancy')
            ->selectRaw("coalesce(sum(case when status = 'vacant' then 1 else 0 end), 0) as vacant_count")
            ->first();

        return [
            'total_rooms' => (int) $stats->total_rooms,
            'total_capacity' => (int) $stats->total_capacity,
            'total_max_occupancy' => (int) $stats->total_max_occupancy,
            'vacant_count' => (int) $stats->vacant_count,
        ];
    }

    public function create(array $data): HostelRoom
    {
        return $this->room->create($data)->refresh();
    }

    public function update(HostelRoom $room, array $data): HostelRoom
    {
        $room->update($data);

        return $room->refresh();
    }
}
