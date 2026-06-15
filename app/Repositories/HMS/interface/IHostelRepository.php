<?php

namespace App\Repositories\HMS\interface;

use App\Models\HMS\Hostel;
use App\Repositories\Base\Interface\IBaseRepository;

interface IHostelRepository extends IBaseRepository
{
    public function create(array $data): Hostel;

    public function update(Hostel $hostel, array $data): Hostel;
}
