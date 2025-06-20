<?php

namespace App\Repositories\Students\interface;

use App\DTO\Students\CreateApplicationDto;
use App\Http\Filters\Students\StudentFilter;
use App\Models\Students\Student;
use App\Repositories\Base\Interface\IBaseRepository;
interface IStudentRepository extends IBaseRepository
{
    public function create(CreateApplicationDto $dto);

    public function update(Student $student, CreateApplicationDto $dto);

    public function allFilter($columns = ['*'], ?StudentFilter $filters=null);
}
