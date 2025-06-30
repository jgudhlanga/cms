<?php

namespace App\Repositories\Institution\interface;

use App\DTO\Institution\IntakePeriodDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Institution\IntakePeriod;
use App\Repositories\Base\Interface\IBaseRepository;

interface IIntakePeriodRepository extends IBaseRepository
{
    public function create(IntakePeriodDto $dto);

    public function update(IntakePeriod $intakePeriod, IntakePeriodDto $dto);

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null);
}
