<?php

namespace App\Repositories\Institution\interface;

use App\DTO\Institution\LevelDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Institution\Level;
use App\Repositories\Base\Interface\IBaseRepository;

interface ILevelRepository extends IBaseRepository
{
    public function create(LevelDto $dto);

    public function update(Level $level, LevelDto $dto);

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null);
}
