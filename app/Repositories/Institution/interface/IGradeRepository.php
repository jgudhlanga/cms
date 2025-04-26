<?php

namespace App\Repositories\Grades\interface;

use App\DTO\Institution\GradeDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Institution\Grade;
use App\Repositories\Base\Interface\IBaseRepository;

interface IGradeRepository extends IBaseRepository
{
    public function create(GradeDto $dto);

    public function update(Grade $grade, GradeDto $dto);

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null);
}
