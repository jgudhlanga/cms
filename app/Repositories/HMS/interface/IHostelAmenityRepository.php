<?php

namespace App\Repositories\HMS\interface;

use App\Models\HMS\HostelAmenity;
use App\Repositories\Base\Interface\IBaseRepository;

interface IHostelAmenityRepository extends IBaseRepository
{
    public function create(array $data): HostelAmenity;

    public function update(HostelAmenity $hostelAmenity, array $data): HostelAmenity;
}
