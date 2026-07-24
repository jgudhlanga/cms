<?php

namespace App\Repositories\Rbac\Interface;

use App\DTO\Rbac\PermissionDto;
use App\Http\Filters\Rbac\PermissionFilter;
use App\Models\Rbac\Permission;
use App\Repositories\Base\Interface\IBaseRepository;

interface IPermissionRepository extends IBaseRepository
{
    public function create(PermissionDto $dto);

    public function update(Permission $permission, PermissionDto $dto);

    public function allFilter($columns = ['*'], PermissionFilter $filters = null);
}
