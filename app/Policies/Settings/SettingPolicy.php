<?php

namespace App\Policies\Settings;

use App\Models\Users\User;

class SettingPolicy
{
    public function viewSettings(User $user): bool
    {
        return $user->can('root:manage') || $user->can('view:settings');

    }

    public function createSettings(User $user): bool
    {
        return $user->can('root:manage') || $user->can('create:settings');
    }

    public function updateSettings(User $user): bool
    {
        return $user->can('root:manage') || $user->can('update:settings');
    }

    public function deleteSettings(User $user): bool
    {

        return $user->can('root:manage') || $user->can('delete:settings');
    }

    public function restoreSettings(User $user): bool
    {
        return $user->can('root:manage') || $user->can('restore:settings');
    }

    public function forceDeleteSettings(User $user): bool
    {
        return $user->can('root:manage') || $user->can('forceDelete:settings');
    }

    public function importSettings(User $user): bool
    {
        return $user->can('root:manage') || $user->can('import:settings');
    }

    public function exportSettings(User $user): bool
    {
        return $user->can('root:manage') || $user->can('export:settings');
    }
}
