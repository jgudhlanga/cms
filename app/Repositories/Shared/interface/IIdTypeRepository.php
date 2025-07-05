<?php

namespace App\Repositories\Shared\interface;

use App\DTO\Shared\IdTypeDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Shared\IdType;
use App\Repositories\Base\Interface\IBaseRepository;

interface IIdTypeRepository extends IBaseRepository
{
    public function create(IdTypeDto $dto);

    public function update(IdType $idType, IdTypeDto $dto);

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null);
}
