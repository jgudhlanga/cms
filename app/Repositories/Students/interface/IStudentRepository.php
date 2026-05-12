<?php

namespace App\Repositories\Students\interface;

use App\DTO\Students\CreateApplicationDto;
use App\DTO\Students\CreateStudentApplicationDto;
use App\DTO\Students\UpdateStudentDto;
use App\Http\Filters\Students\StudentFilter;
use App\Models\Students\Student;
use App\Repositories\Base\Interface\IBaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;

interface IStudentRepository extends IBaseRepository
{
    public function create(CreateApplicationDto|CreateStudentApplicationDto $dto);

    public function update(Student $student, UpdateStudentDto $dto);

    public function allFilter($columns = ['*'], ?StudentFilter $filters = null);

    public function paginateForIndex(array $filters = []): LengthAwarePaginator;
}
