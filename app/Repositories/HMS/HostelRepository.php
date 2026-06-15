<?php

namespace App\Repositories\HMS;

use App\Models\HMS\Hostel;
use App\Repositories\Base\BaseRepository;
use App\Repositories\HMS\interface\IHostelRepository;

class HostelRepository extends BaseRepository implements IHostelRepository
{
    public function __construct(protected Hostel $hostel)
    {
        parent::__construct($this->hostel);
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
