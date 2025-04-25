<?php

namespace App\Repositories\Communications\interface;

use App\DTO\Communications\CommunicationMethodDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Communications\CommunicationMethod;
use App\Repositories\Base\Interface\IBaseRepository;

interface ICommunicationMethodRepository extends IBaseRepository
{
    public function create(CommunicationMethodDto $dto);

    public function update(CommunicationMethod $communicationMethod, CommunicationMethodDto $dto);

    public function allFilter($columns = ['*'], SharedTitleFilter $filters = null);
}
