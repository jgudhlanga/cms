<?php

namespace App\Repositories\Acl\Interface;

use App\DTO\Acl\RoleDto;
use App\Http\Filters\Acl\RoleFilter;
use App\Models\Acl\Role;
use App\Repositories\Base\Interface\IBaseRepository;

interface IRoleRepository extends IBaseRepository
{
    public function create(RoleDto $dto);

    public function update(Role $role, RoleDto $dto);

    public function allFilter($columns = ['*'], ?RoleFilter $filters = null);
}
