<?php

namespace App\Repositories\Rbac\Interface;

use App\DTO\Rbac\RoleGroupDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Rbac\RoleGroup;
use App\Repositories\Base\Interface\IBaseRepository;

interface IRoleGroupRepository extends IBaseRepository
{
    public function create(RoleGroupDto $dto);

    public function update(RoleGroup $roleGroup, RoleGroupDto $dto);

    public function allFilter($columns = ['*'], ?SharedNameFilter $filters = null);
}
