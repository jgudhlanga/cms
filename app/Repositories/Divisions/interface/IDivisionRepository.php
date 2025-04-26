<?php

namespace App\Repositories\Divisions\interface;

use App\DTO\Institution\DivisionDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Institution\Division;
use App\Repositories\Base\Interface\IBaseRepository;

interface IDivisionRepository extends IBaseRepository
{
    public function create(DivisionDto $dto);

    public function update(Division $division, DivisionDto $dto);

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null);
}
