<?php

namespace App\Policies\Institution;

use App\Models\Institution\Grade;
use App\Models\Users\User;

class GradePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:grades');
    }

    public function view(User $user, Grade $grade): bool
    {
        return $user->can('viewAny:grades') || $user->can('view:grades');
    }

    public function create(User $user): bool
    {
        return $user->can('create:grades');
    }

    public function update(User $user, Grade $grade): bool
    {
        return $user->can('update:grades');
    }

    public function delete(User $user, Grade $grade): bool
    {
        return $user->can('delete:grades');
    }

    public function restore(User $user, Grade $grade): bool
    {
        return $user->can('restore:grades');
    }

    public function forceDelete(User $user, Grade $grade): bool
    {
        return $user->can('forceDelete:grades');
    }
}
