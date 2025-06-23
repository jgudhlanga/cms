<?php

namespace App\Repositories\Shared\interface;

use App\DTO\Religions\ReligionDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Shared\Religion;
use App\Repositories\Base\Interface\IBaseRepository;

interface IReligionRepository extends IBaseRepository
{
    public function create(ReligionDto $dto);

    public function update(Religion $religion, ReligionDto $dto);

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null);
}
