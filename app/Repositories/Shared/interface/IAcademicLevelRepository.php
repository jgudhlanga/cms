<?php

namespace App\Repositories\Shared\interface;

use App\DTO\AcademicLevels\AcademicLevelDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Shared\AcademicLevel;
use App\Repositories\Base\Interface\IBaseRepository;

interface IAcademicLevelRepository extends IBaseRepository
{
    public function create(AcademicLevelDto $dto);

    public function update(AcademicLevel $academicLevel, AcademicLevelDto $dto);

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null);
}
