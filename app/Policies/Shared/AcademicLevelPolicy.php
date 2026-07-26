<?php

namespace App\Policies\Shared;

use App\Models\Shared\AcademicLevel;
use App\Models\Users\User;

class AcademicLevelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:academic-levels');
    }

    public function view(User $user, AcademicLevel $academicLevel): bool
    {
        return $user->can('viewAny:academic-levels') || $user->can('view:academic-levels');
    }

    public function create(User $user): bool
    {
        return $user->can('create:academic-levels');
    }

    public function update(User $user, AcademicLevel $academicLevel): bool
    {
        return $user->can('update:academic-levels');
    }

    public function delete(User $user, AcademicLevel $academicLevel): bool
    {
        return $user->can('delete:academic-levels');
    }

    public function restore(User $user, AcademicLevel $academicLevel): bool
    {
        return $user->can('restore:academic-levels');
    }

    public function forceDelete(User $user, AcademicLevel $academicLevel): bool
    {
        return $user->can('forceDelete:academic-levels');
    }
}
