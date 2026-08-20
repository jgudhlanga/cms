<?php

namespace App\Policies\Finance;

use App\Models\Users\User;

class FinancePolicy
{
    public function viewFinances(User $user): bool
    {
        return $user->can('root:manage')
            || $user->can('viewAny:finances')
            || $user->can('view:finances');
    }

    public function exportToPastel(User $user): bool
    {
        return $user->can('root:manage')
            || $user->can('export-to-pastel:finances');
    }
}
