<?php

namespace App\Repositories\Shared\interface;

use App\DTO\Shared\EmploymentTypeDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Shared\EmploymentType;
use App\Repositories\Base\Interface\IBaseRepository;

interface IEmploymentTypeRepository extends IBaseRepository
{
    public function create(EmploymentTypeDto $dto);

    public function update(EmploymentType $employmentType, EmploymentTypeDto $dto);

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null);
}
