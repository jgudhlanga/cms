<?php

namespace App\Repositories\Acl\Interface;

use App\DTO\Acl\PermissionDto;
use App\Http\Filters\Acl\PermissionFilter;
use App\Models\Acl\Permission;
use App\Repositories\Base\Interface\IBaseRepository;

interface IPermissionRepository extends IBaseRepository
{
    public function create(PermissionDto $dto);

    public function update(Permission $permission, PermissionDto $dto);

    public function allFilter($columns = ['*'], PermissionFilter $filters = null);
}
