<?php

namespace App\Repositories\HMS;

use App\Models\HMS\HostelAmenity;
use App\Repositories\Base\BaseRepository;
use App\Repositories\HMS\interface\IHostelAmenityRepository;

class HostelAmenityRepository extends BaseRepository implements IHostelAmenityRepository
{
    public function __construct(protected HostelAmenity $hostelAmenity)
    {
        parent::__construct($this->hostelAmenity);
    }

    public function create(array $data): HostelAmenity
    {
        return $this->hostelAmenity->create([
            'name' => trim((string) ($data['name'] ?? '')),
        ])->refresh();
    }

    public function update(HostelAmenity $hostelAmenity, array $data): HostelAmenity
    {
        $hostelAmenity->update([
            'name' => trim((string) ($data['name'] ?? '')),
        ]);

        return $hostelAmenity->refresh();
    }
}
