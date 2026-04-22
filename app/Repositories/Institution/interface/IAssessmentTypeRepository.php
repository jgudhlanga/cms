<?php

namespace App\Repositories\Institution\interface;

use App\DTO\Institution\AssessmentTypeDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Institution\AssessmentType;
use App\Repositories\Base\Interface\IBaseRepository;

interface IAssessmentTypeRepository extends IBaseRepository
{
    public function create(AssessmentTypeDto $dto);

    public function update(AssessmentType $assessmentType, AssessmentTypeDto $dto);

    public function allFilter($columns = ['*'], ?SharedNameFilter $filters = null);
}
