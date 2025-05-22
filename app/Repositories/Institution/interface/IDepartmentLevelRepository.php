<?php

namespace App\Repositories\Institution\interface;

use App\DTO\Institution\DepartmentLevelDto;
use App\DTO\Institution\DepartmentLevelRequirementsDto;
use App\Http\Filters\Institution\DepartmentMetaDataFilter;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Base\Interface\IBaseRepository;

interface IDepartmentLevelRepository extends IBaseRepository
{
    public function syncDepartmentLevels(InstitutionDepartment $institutionDepartment, DepartmentLevelDto $dto);

    public function updateDepartmentLevelRequirements(DepartmentLevel $departmentLevel, DepartmentLevelRequirementsDto $dto);
}
