<?php

namespace App\Policies\HMS;

use App\Models\HMS\HmsSetting;
use App\Models\Users\User;

class HmsSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:hms-settings');
    }

    public function view(User $user, HmsSetting $hmsSetting): bool
    {
        return $user->can('viewAny:hms-settings') || $user->can('view:hms-settings');
    }

    public function create(User $user): bool
    {
        return $user->can('create:hms-settings');
    }

    public function update(User $user, HmsSetting $hmsSetting): bool
    {
        return $user->can('update:hms-settings', $hmsSetting);
    }

    public function delete(User $user, HmsSetting $hmsSetting): bool
    {
        return $user->can('delete:hms-settings', $hmsSetting);
    }

    public function restore(User $user, HmsSetting $hmsSetting): bool
    {
        return $user->can('restore:hms-settings', $hmsSetting);
    }

    public function forceDelete(User $user, HmsSetting $hmsSetting): bool
    {
        return $user->can('forceDelete:hms-settings', $hmsSetting);
    }
}
