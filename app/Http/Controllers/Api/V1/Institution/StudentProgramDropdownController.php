<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\Institution\DepartmentFilter;
use App\Http\Filters\Institution\InstitutionDepartmentFilter;
use App\Http\Resources\Institution\DepartmentLevelCourseResource;
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Institution\interface\IInstitutionDepartmentRepository;
use App\Traits\HttpUtil;
use Illuminate\Http\Request;

class StudentProgramDropdownController extends ApiDropdownController
{
    use HttpUtil;

    public function __construct(protected IInstitutionDepartmentRepository $repository)
    {
    }

    public function institutionDepartments(InstitutionDepartmentFilter $filters)
    {
        return InstitutionDepartmentResource::collection($this->repository->allFilter(['*'], $filters));
    }

    public function departmentLevels(InstitutionDepartment $institutionDepartment)
    {
        return DepartmentLevelResource::collection($institutionDepartment->departmentLevels);
    }

    public function departmentCourses(DepartmentLevel $departmentLevel)
    {
        return DepartmentLevelCourseResource::collection($departmentLevel->courses);
    }
}
