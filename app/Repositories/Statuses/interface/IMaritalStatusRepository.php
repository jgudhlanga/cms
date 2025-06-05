<?php

namespace App\Repositories\Statuses\interface;

use App\DTO\Statuses\MaritalStatusDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Statuses\MaritalStatus;

interface IMaritalStatusRepository
{
    public function create(MaritalStatusDto $dto);

    public function update(MaritalStatus $maritalStatus, MaritalStatusDto $dto);

    public function allFilter($columns = ['*'], SharedTitleFilter $filters = null);
}
