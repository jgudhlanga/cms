<?php

namespace App\Repositories\HMS;

use App\Models\HMS\Hostel;
use App\Repositories\Base\BaseRepository;
use App\Repositories\HMS\interface\IHostelRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class HostelRepository extends BaseRepository implements IHostelRepository
{
    public function __construct(protected Hostel $hostel)
    {
        parent::__construct($this->hostel);
    }

    public function paginateForIndex(array $filters = []): LengthAwarePaginator
    {
        $query = $this->hostel->query()->orderBy('name')->orderBy('deleted_at');

        if (array_key_exists('search', $filters) && is_string($filters['search']) && $filters['search'] !== '') {
            $search = trim($filters['search']);

            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if (array_key_exists('with_trashed', $filters) && $filters['with_trashed']) {
            $query->withTrashed();
        }

        return $query->paginate()->withQueryString();
    }

    public function create(array $data): Hostel
    {
        return $this->hostel->create($data)->refresh();
    }

    public function update(Hostel $hostel, array $data): Hostel
    {
        $hostel->update($data);

        return $hostel->refresh();
    }
}

