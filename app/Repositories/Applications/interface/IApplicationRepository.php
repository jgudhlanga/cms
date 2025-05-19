<?php

namespace App\Repositories\Applications\interface;

use App\DTO\Applications\ApplicationDto;
use App\Http\Filters\Applications\ApplicationFilter;
use App\Models\Applications\Application;
use App\Repositories\Base\Interface\IBaseRepository;

interface IApplicationRepository extends IBaseRepository
{
    public function create(ApplicationDto $dto);

    public function update(Application $application, ApplicationDto $dto);

    public function allFilter($columns = ['*'], ApplicationFilter $filters = null);
}
