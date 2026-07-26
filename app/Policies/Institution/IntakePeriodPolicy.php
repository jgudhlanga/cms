<?php

namespace App\Policies\Institution;

use App\Models\Institution\IntakePeriod;
use App\Models\Users\User;

class IntakePeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:intake-periods');
    }

    public function view(User $user, IntakePeriod $intakePeriod): bool
    {
        return $user->can('viewAny:intake-periods') || $user->can('view:intake-periods');
    }

    public function create(User $user): bool
    {
        return $user->can('create:intake-periods');
    }

    public function update(User $user, IntakePeriod $intakePeriod): bool
    {
        return $user->can('update:intake-periods');
    }

    public function delete(User $user, IntakePeriod $intakePeriod): bool
    {
        return $user->can('delete:intake-periods');
    }

    public function restore(User $user, IntakePeriod $intakePeriod): bool
    {
        return $user->can('restore:intake-periods');
    }

    public function forceDelete(User $user, IntakePeriod $intakePeriod): bool
    {
        return $user->can('forceDelete:intake-periods');
    }
}
