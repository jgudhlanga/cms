<?php

namespace App\Repositories\Rbac;

use App\DTO\Rbac\RoleDto;
use App\Enums\Rbac\RoleEnum;
use App\Http\Filters\Rbac\PermissionFilter;
use App\Http\Filters\Rbac\RoleFilter;
use App\Models\Rbac\Role;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Rbac\Interface\IRoleRepository;

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
        $excludes = [
            RoleEnum::SUPER_ADMINISTRATOR->value,
            RoleEnum::STUDENT->value,
            RoleEnum::SUPER_USER->value,
        ];
        return $this->role
            ->select($columns)
            ->filter($filters)
            ->whereNotIn('slug', $excludes)
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
