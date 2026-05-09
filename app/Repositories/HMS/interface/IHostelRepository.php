<?php

namespace App\Repositories\HMS\interface;

use App\Models\HMS\Hostel;
use App\Repositories\Base\Interface\IBaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;

interface IHostelRepository extends IBaseRepository
{
    public function paginateForIndex(array $filters = []): LengthAwarePaginator;

    public function create(array $data): Hostel;

    public function update(Hostel $hostel, array $data): Hostel;
}

