<?php

namespace App\Policies\Shared;

use App\Models\Shared\EmploymentType;
use App\Models\Users\User;

class EmploymentTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:employment-types');
    }

    public function view(User $user, EmploymentType $employmentType): bool
    {
        return $user->can('viewAny:employment-types') || $user->can('view:employment-types');
    }

    public function create(User $user): bool
    {
        return $user->can('create:employment-types');
    }

    public function update(User $user, EmploymentType $employmentType): bool
    {
        return $user->can('update:employment-types');
    }

    public function delete(User $user, EmploymentType $employmentType): bool
    {
        return $user->can('delete:employment-types');
    }

    public function restore(User $user, EmploymentType $employmentType): bool
    {
        return $user->can('restore:employment-types');
    }

    public function forceDelete(User $user, EmploymentType $employmentType): bool
    {
        return $user->can('forceDelete:employment-types');
    }
}
