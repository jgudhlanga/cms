<?php

namespace App\Repositories\Students\interface;

use App\DTO\Students\StudentApplicationDto;
use App\DTO\Students\ProgramDto;
use App\Http\Filters\Students\StudentApplicationFilter;
use App\Models\Students\StudentApplication;
use App\Repositories\Base\Interface\IBaseRepository;

interface IStudentApplicationRepository extends IBaseRepository
{
    public function create(StudentApplicationDto $dto);

    public function update(StudentApplication $studentApplication, ProgramDto $dto);

    public function allFilter($columns = ['*'], ?StudentApplicationFilter $filters = null);
}
