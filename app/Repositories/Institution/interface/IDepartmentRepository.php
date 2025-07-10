<?php

namespace App\Repositories\Institution\interface;

use App\DTO\Institution\DepartmentDto;
use App\Http\Filters\Institution\DepartmentFilter;
use App\Models\Institution\Department;
use App\Repositories\Base\Interface\IBaseRepository;

interface IDepartmentRepository extends IBaseRepository
{
    public function create(DepartmentDto $dto);

    public function update(Department $department, DepartmentDto $dto);

    public function allFilter($columns = ['*'], DepartmentFilter $filters = null);
}
