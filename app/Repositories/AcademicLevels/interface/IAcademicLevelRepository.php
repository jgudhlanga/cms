<?php

namespace App\Repositories\AcademicLevels\interface;

use App\DTO\AcademicLevels\AcademicLevelDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\AcademicLevels\AcademicLevel;
use App\Repositories\Base\Interface\IBaseRepository;

interface IAcademicLevelRepository extends IBaseRepository
{
    public function create(AcademicLevelDto $dto);

    public function update(AcademicLevel $academicLevel, AcademicLevelDto $dto);

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null);
}
