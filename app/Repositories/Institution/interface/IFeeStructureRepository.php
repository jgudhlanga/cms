<?php

namespace App\Repositories\Institution\interface;

use App\DTO\Institution\FeeStructureDto;
use App\Http\Filters\Institution\FeeStructureFilter;
use App\Models\Institution\FeeStructure;
use App\Repositories\Base\Interface\IBaseRepository;

interface IFeeStructureRepository extends IBaseRepository
{
    public function create(FeeStructureDto $dto);

    public function update(FeeStructure $feeStructure, FeeStructureDto $dto);

    public function allFilter($columns = ['*'], FeeStructureFilter $filters = null);
}
