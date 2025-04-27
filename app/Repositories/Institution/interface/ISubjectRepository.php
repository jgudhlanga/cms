<?php

namespace App\Repositories\Institution\interface;

use App\DTO\Institution\SubjectDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Institution\Subject;
use App\Repositories\Base\Interface\IBaseRepository;

interface ISubjectRepository extends IBaseRepository
{
    public function create(SubjectDto $dto);

    public function update(Subject $subject, SubjectDto $dto);

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null);
}
