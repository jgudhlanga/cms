<?php

namespace App\Repositories\Acl;

use App\DTO\Acl\PermissionDto;
use App\Http\Filters\Acl\PermissionFilter;
use App\Models\Acl\Permission;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Acl\Interface\IPermissionRepository;

class PermissionRepository extends BaseRepository implements IPermissionRepository
{
    public function __construct(protected Permission $permission)
    {
        parent::__construct($this->permission);
    }

    public function create(PermissionDto $dto): Permission
    {
        return $this->permission->create([
            'name' => $dto->name,
            'description' => $dto->description,
			'module_id' => $dto->module_id,
        ])->refresh();
    }

    public function update(Permission $permission, PermissionDto $dto): Permission
    {
        return tap($permission)->update([
            'name' => $dto->name,
            'description' => $dto->description,
			'module_id' => $dto->module_id,
        ]);
    }

    public function allFilter($columns = ['*'], ?PermissionFilter $filters = null)
    {
        return $this->permission
			->select($columns)
			->filter($filters)
			->orderBy('module_id')
			->orderBy('name')
			->orderBy('deleted_at')
			->paginate()
			->withQueryString();
    }
}
