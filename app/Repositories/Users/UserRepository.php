<?php

namespace App\Repositories\Users;

use App\DTO\Users\UpdateUserDto;
use App\DTO\Users\UserDto;
use App\Http\Filters\Users\UserFilter;
use App\Models\Users\User;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Users\interface\IUserRepository;

class UserRepository extends BaseRepository implements IUserRepository
{
    public function __construct(protected User $user)
    {
        parent::__construct($this->user);
    }

    public function create(UserDto $dto): User
    {
        $user = $this->user->create($this->getFields($dto));
        if (!empty($dto->role_ids)) {
            $user->assignRole($dto->role_ids);
        }
        return $user->refresh();
    }

    public function update(User $user, UpdateUserDto $dto): User
    {
        if (!empty($dto->role_ids)) {
            $user->assignRole($dto->role_ids);
        }
        return tap($user)->update($this->getUpdateFields($dto));
    }

    public function allFilter($columns = ['*'], UserFilter $filters = null)
    {
        return $this->user
            ->with(
                'tenant',
                'status',
                'roles',
                'roles.permissions',
                'permissions',
                'studentProfile',
                'studentProfile.gender',
                'studentProfile.title',
                'studentProfile.country',
                'studentProfile.maritalStatus',
                'studentProfile.idType',
                'staffProfile',
                'staffProfile.gender',
                'staffProfile.title',
                'staffProfile.maritalStatus',
                'staffProfile.idType',
                'staffProfile.country',
                'staffProfile.employmentType',
                'staffProfile.institutionDepartments.department',
            )
            ->select($columns)
            ->filter($filters)
            ->orderBy('first_name')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    private function getFields(UserDto $dto): array
    {
        return [
            'tenant_id' => $dto->tenant_id,
            'first_name' => $dto->first_name,
            'middle_name' => $dto->middle_name,
            'last_name' => $dto->last_name,
            'email' => trim(strtolower($dto->email)),
            'phone_number' => $dto->phone_number,
            'status_id' => $dto->status_id,
            'password' => $dto->password,
        ];
    }

    private function getUpdateFields(UpdateUserDto $dto): array
    {
        return [
            'first_name' => $dto->first_name,
            'middle_name' => $dto->middle_name,
            'last_name' => $dto->last_name,
            'email' => $dto->email,
            'phone_number' => $dto->phone_number,
        ];
    }
}
