<?php

namespace App\Repositories\ModesOfStudy\interface;

use App\DTO\Institution\ModeOfStudyDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Institution\ModeOfStudy;
use App\Repositories\Base\Interface\IBaseRepository;

interface IModeOfStudyRepository extends IBaseRepository
{
    public function create(ModeOfStudyDto $dto);

    public function update(ModeOfStudy $modeOfStudy, ModeOfStudyDto $dto);

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null);
}
