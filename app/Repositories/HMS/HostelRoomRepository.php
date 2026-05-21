<?php

namespace App\Repositories\HMS;

use App\Models\HMS\HostelRoom;
use App\Repositories\Base\BaseRepository;
use App\Repositories\HMS\interface\IHostelRoomRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class HostelRoomRepository extends BaseRepository implements IHostelRoomRepository
{
    public function __construct(protected HostelRoom $room)
    {
        parent::__construct($this->room);
    }

    public function paginateForIndex(array $filters = []): LengthAwarePaginator
    {
        $query = $this->room->query()
            ->with('hostel:id,name')
            ->orderBy('name');

        if (array_key_exists('search', $filters) && is_string($filters['search']) && $filters['search'] !== '') {
            $search = trim($filters['search']);

            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('floor_number', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('room_type', 'like', "%{$search}%");
            });
        }

        if (array_key_exists('hostel', $filters) && is_string($filters['hostel']) && $filters['hostel'] !== '') {
            $hostel = trim($filters['hostel']);
            $query->whereHas('hostel', function ($q) use ($hostel): void {
                $q->where('name', 'like', "%{$hostel}%");
            });
        }

        if (array_key_exists('with_trashed', $filters) && $filters['with_trashed']) {
            $query->withTrashed();
        }

        return $query->paginate($this->room->getPerPage())->withQueryString();
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
