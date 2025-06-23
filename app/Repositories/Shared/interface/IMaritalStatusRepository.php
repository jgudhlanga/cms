<?php

namespace App\Repositories\Shared\interface;

use App\DTO\Shared\MaritalStatusDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Shared\MaritalStatus;

interface IMaritalStatusRepository
{
    public function create(MaritalStatusDto $dto);

    public function update(MaritalStatus $maritalStatus, MaritalStatusDto $dto);

    public function allFilter($columns = ['*'], SharedTitleFilter $filters = null);
}
