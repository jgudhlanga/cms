<?php

namespace App\Repositories\Institution\interface;

use App\DTO\Institution\DepartmentCourseDto;
use App\Http\Filters\Institution\DepartmentMetaDataFilter;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Base\Interface\IBaseRepository;

interface IDepartmentCourseRepository extends IBaseRepository
{
    public function syncDepartmentCourses(InstitutionDepartment $institutionDepartment, DepartmentCourseDto $dto);

}
