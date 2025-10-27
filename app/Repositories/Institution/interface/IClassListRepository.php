<?php

namespace App\Repositories\Institution\interface;

use App\DTO\Enrolments\ClassListDto;
use App\Models\Enrolments\ClassList;
use App\Repositories\Base\Interface\IBaseRepository;

interface IClassListRepository extends IBaseRepository
{
    public function create(ClassListDto $dto);

    public function update(ClassList $classList, ClassListDto $dto);
}
