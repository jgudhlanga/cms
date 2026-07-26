<?php

namespace App\Policies\Institution;

use App\Models\Institution\ModeOfStudy;
use App\Models\Users\User;

class ModeOfStudyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:mode-of-studies');
    }

    public function view(User $user, ModeOfStudy $modeOfStudy): bool
    {
        return $user->can('viewAny:mode-of-studies') || $user->can('view:mode-of-studies');
    }

    public function create(User $user): bool
    {
        return $user->can('create:mode-of-studies');
    }

    public function update(User $user, ModeOfStudy $modeOfStudy): bool
    {
        return $user->can('update:mode-of-studies');
    }

    public function delete(User $user, ModeOfStudy $modeOfStudy): bool
    {
        return $user->can('delete:mode-of-studies');
    }

    public function restore(User $user, ModeOfStudy $modeOfStudy): bool
    {
        return $user->can('restore:mode-of-studies');
    }

    public function forceDelete(User $user, ModeOfStudy $modeOfStudy): bool
    {
        return $user->can('forceDelete:mode-of-studies');
    }
}
