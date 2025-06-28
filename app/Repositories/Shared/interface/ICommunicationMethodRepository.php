<?php

namespace App\Repositories\Shared\interface;

use App\DTO\Shared\CommunicationMethodDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Shared\CommunicationMethod;
use App\Repositories\Base\Interface\IBaseRepository;

interface ICommunicationMethodRepository extends IBaseRepository
{
    public function create(CommunicationMethodDto $dto);

    public function update(CommunicationMethod $communicationMethod, CommunicationMethodDto $dto);

    public function allFilter($columns = ['*'], SharedTitleFilter $filters = null);
}
