<?php

namespace App\Policies\Institution;

use App\Models\Institution\Subject;
use App\Models\Users\User;

class SubjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:subjects');
    }

    public function view(User $user, Subject $subject): bool
    {
        return $user->can('viewAny:subjects') || $user->can('view:subjects');
    }

    public function create(User $user): bool
    {
        return $user->can('create:subjects');
    }

    public function update(User $user, Subject $subject): bool
    {
        return $user->can('update:subjects');
    }

    public function delete(User $user, Subject $subject): bool
    {
        return $user->can('delete:subjects');
    }

    public function restore(User $user, Subject $subject): bool
    {
        return $user->can('restore:subjects');
    }

    public function forceDelete(User $user, Subject $subject): bool
    {
        return $user->can('forceDelete:subjects');
    }
}
