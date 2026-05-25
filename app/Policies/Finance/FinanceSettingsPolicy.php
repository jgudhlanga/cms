<?php

namespace App\Policies\Finance;

use App\Models\Users\User;

class FinanceSettingsPolicy
{
    public function viewFinanceSettings(User $user): bool
    {
        return $user->can('root:manage')
            || $user->can('viewAny:finance-settings')
            || $user->can('view:finance-settings');
    }

    public function createFinanceSettings(User $user): bool
    {
        return $user->can('root:manage')
            || $user->can('create:finance-settings');
    }

    public function updateFinanceSettings(User $user): bool
    {
        return $user->can('root:manage')
            || $user->can('update:finance-settings');
    }

    public function deleteFinanceSettings(User $user): bool
    {
        return $user->can('root:manage')
            || $user->can('delete:finance-settings');
    }

    public function restoreFinanceSettings(User $user): bool
    {
        return $user->can('root:manage')
            || $user->can('restore:finance-settings');
    }

    public function forceDeleteFinanceSettings(User $user): bool
    {
        return $user->can('root:manage')
            || $user->can('forceDelete:finance-settings');
    }
}
