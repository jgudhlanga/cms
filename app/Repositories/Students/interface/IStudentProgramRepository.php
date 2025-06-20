<?php

namespace App\Repositories\Students\interface;

use App\DTO\Students\StudentProgramDto;
use App\Http\Filters\Students\StudentProgramFilter;
use App\Models\Students\StudentProgram;
use App\Repositories\Base\Interface\IBaseRepository;

interface IStudentProgramRepository extends IBaseRepository
{
    public function create(StudentProgramDto $dto);

    public function update(StudentProgram $studentProgram, StudentProgramDto $dto);

    public function allFilter($columns = ['*'], ?StudentProgramFilter $filters = null);
}
