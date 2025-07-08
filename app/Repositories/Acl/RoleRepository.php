<?php

namespace App\Repositories\Acl;

use App\DTO\Acl\RoleDto;
use App\Http\Filters\Acl\PermissionFilter;
use App\Http\Filters\Acl\RoleFilter;
use App\Models\Acl\Role;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Acl\Interface\IRoleRepository;

class RoleRepository extends BaseRepository implements IRoleRepository
{
	public function __construct(protected Role $role)
	{
		parent::__construct($this->role);
	}

	public function create(RoleDto $dto): Role
	{
		$role = $this->role->create($this->getFields($dto));
		if (!is_null($dto->permissions)) {
			$role->syncPermissions(array_values($dto->permissions));
		}
		return $role->fresh();
	}

	public function update(Role $role, RoleDto $dto): Role
	{
		$role = tap($role)->update($this->getFields($dto));

		if (!is_null($dto->permissions)) {
			$role->syncPermissions(array_values($dto->permissions));
		}
		return $role->fresh();
	}

	public function allFilter($columns = ['*'], ?RoleFilter $filters = null)
	{
		return $this->role
			->select($columns)
			->filter($filters)
			->orderBy('role_group_id')
			->orderBy('name')
			->orderBy('deleted_at')
			->paginate()
			->withQueryString();
	}

    /**
     * @param RoleDto $dto
     * @return array
     */
    public function getFields(RoleDto $dto): array
    {
        return [
            'name' => $dto->name,
            'description' => $dto->description,
        ];
    }

}
