<?php

namespace App\Repositories\Institution\interface;

use App\DTO\Institution\DepartmentCourseDto;
use App\DTO\Institution\DepartmentCourseUpdateDto;
use App\DTO\Institution\CourseRequirementsDto;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Base\Interface\IBaseRepository;

interface IDepartmentCourseRepository extends IBaseRepository
{
    public function syncDepartmentCourses(InstitutionDepartment $institutionDepartment, DepartmentCourseDto $dto);

    public function update(DepartmentCourse $departmentCourse, DepartmentCourseUpdateDto $dto);

    public function updateLevelCourseRequirements(DepartmentCourse $departmentCourse, CourseRequirementsDto $dto);

}
