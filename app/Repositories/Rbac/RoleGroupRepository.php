<?php

namespace App\Repositories\Rbac;

use App\DTO\Rbac\RoleGroupDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Rbac\RoleGroup;
use App\Repositories\Rbac\Interface\IRoleGroupRepository;
use App\Repositories\Base\BaseRepository;

class RoleGroupRepository extends BaseRepository implements IRoleGroupRepository
{
    public function __construct(protected RoleGroup $roleGroup)
    {
        parent::__construct($this->roleGroup);
    }

    public function create(RoleGroupDto $dto): RoleGroup
    {
        return $this->roleGroup->create($this->gerFields($dto));
    }

    public function update(RoleGroup $roleGroup, RoleGroupDto $dto): RoleGroup
    {
        $role = tap($roleGroup)->update($this->gerFields($dto));
        return $roleGroup->fresh();
    }

    public function allFilter($columns = ['*'], ?SharedNameFilter $filters = null)
    {
        return $this->roleGroup
            ->select($columns)
            ->filter($filters)
            ->orderBy('name')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    /**
     * @param RoleGroupDto $dto
     * @return array
     */
    public function gerFields(RoleGroupDto $dto): array
    {
        return [
            'name' => $dto->name,
            'description' => $dto->description,
        ];
    }

}
