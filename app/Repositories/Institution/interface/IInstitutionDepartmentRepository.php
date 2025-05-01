<?php

namespace App\Repositories\Institution\interface;

use App\DTO\Institution\InstitutionDepartmentDto;
use App\Http\Filters\Institution\DepartmentFilter;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Base\Interface\IBaseRepository;

interface IInstitutionDepartmentRepository extends IBaseRepository
{
    public function syncInstitutionDepartment(InstitutionDepartmentDto $dto);

    public function allFilter($columns = ['*'], DepartmentFilter $filters = null);
}
