<?php

namespace App\Repositories\Shared\interface;

use App\DTO\Shared\ApplicationStepDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Shared\ApplicationStep;
use App\Repositories\Base\Interface\IBaseRepository;

interface IApplicationStepRepository extends IBaseRepository
{
    public function create(ApplicationStepDto $dto);

    public function update(ApplicationStep $applicationStep, ApplicationStepDto $dto);

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null);
}
