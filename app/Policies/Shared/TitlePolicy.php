<?php

namespace App\Policies\Shared;

use App\Models\Shared\Title;
use App\Models\Users\User;

class TitlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:titles');
    }

    public function view(User $user, Title $title): bool
    {
        return $user->can('viewAny:titles') || $user->can('view:titles');
    }

    public function create(User $user): bool
    {
        return $user->can('create:titles');
    }

    public function update(User $user, Title $title): bool
    {
        return $user->can('update:titles');
    }

    public function delete(User $user, Title $title): bool
    {
        return $user->can('delete:titles');
    }

    public function restore(User $user, Title $title): bool
    {
        return $user->can('restore:titles');
    }

    public function forceDelete(User $user, Title $title): bool
    {
        return $user->can('forceDelete:titles');
    }
}
