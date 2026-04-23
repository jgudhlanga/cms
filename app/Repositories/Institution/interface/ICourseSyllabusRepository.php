<?php

namespace App\Repositories\Institution\interface;

use App\DTO\Institution\CourseSyllabusDto;
use App\Models\Institution\CourseSyllabus;
use App\Repositories\Base\Interface\IBaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ICourseSyllabusRepository extends IBaseRepository
{
    public function create(CourseSyllabusDto $dto): CourseSyllabus;

    public function update(CourseSyllabus $courseSyllabus, CourseSyllabusDto $dto): CourseSyllabus;

    public function allByInstitutionDepartment(int $institutionDepartmentId): LengthAwarePaginator;
}
