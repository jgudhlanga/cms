<?php

namespace App\Repositories\Shared\interface;

use App\DTO\Shared\NextOfKinDto;
use App\Models\Shared\NextOfKin;
use App\Repositories\Base\Interface\IBaseRepository;
use Illuminate\Database\Eloquent\Model;

interface INextOfKinRepository extends IBaseRepository
{
    public function create(Model $model, NextOfKinDto $dto);

    public function update(NextOfKin $nextOfKin, NextOfKinDto $dto);

}
