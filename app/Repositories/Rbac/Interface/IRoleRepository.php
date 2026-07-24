<?php

namespace App\Repositories\Rbac\Interface;

use App\DTO\Rbac\RoleDto;
use App\Http\Filters\Rbac\RoleFilter;
use App\Models\Rbac\Role;
use App\Repositories\Base\Interface\IBaseRepository;

interface IRoleRepository extends IBaseRepository
{
    public function create(RoleDto $dto);

    public function update(Role $role, RoleDto $dto);

    public function allFilter($columns = ['*'], ?RoleFilter $filters = null);
}
