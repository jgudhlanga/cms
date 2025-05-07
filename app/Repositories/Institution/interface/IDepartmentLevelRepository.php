<?php

namespace App\Repositories\Institution\interface;

use App\DTO\Institution\DepartmentLevelDto;
use App\Http\Filters\Institution\DepartmentLevelFilter;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Base\Interface\IBaseRepository;

interface IDepartmentLevelRepository extends IBaseRepository
{
    public function syncDepartmentLevels(InstitutionDepartment $institutionDepartment, DepartmentLevelDto $dto);

    public function allFilter($columns = ['*'], DepartmentLevelFilter $filters = null);
}
