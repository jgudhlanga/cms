<?php

namespace App\Repositories\Shared\interface;

use App\DTO\Shared\FeeTypeDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Shared\FeeType;
use App\Repositories\Base\Interface\IBaseRepository;

interface IFeeTypeRepository extends IBaseRepository
{
    public function create(FeeTypeDto $dto);

    public function update(FeeType $feeType, FeeTypeDto $dto);

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null);
}
