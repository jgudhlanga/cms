<?php

namespace App\Repositories\Users\interface;

use App\DTO\Users\UpdateUserDto;
use App\DTO\Users\UserDto;
use App\Http\Filters\Users\UserFilter;
use App\Models\Users\User;
use App\Repositories\Base\Interface\IBaseRepository;

interface IUserRepository extends IBaseRepository
{
    public function create(UserDto $dto);

    public function update(User $user, UpdateUserDto $dto);

    public function allFilter($columns = ['*'], UserFilter $filters = null);
}
