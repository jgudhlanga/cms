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
}
