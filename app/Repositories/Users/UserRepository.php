<?php

namespace App\Repositories\Users;

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
        return $this->user->create($this->getFields($dto))->refresh();
    }

    public function update(User $user, UserDto $dto): User
    {
        return tap($user)->update($this->getFields($dto));
    }

    public function allFilter($columns = ['*'], UserFilter $filters = null)
    {
        return $this->user
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
            'email' => $dto->email,
            'status_id' => $dto->status_id,
            'password' => $dto->password,
        ];
    }
}
