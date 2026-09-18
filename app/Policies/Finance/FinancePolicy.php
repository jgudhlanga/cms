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

    public function exportForBilling(User $user): bool
    {
        return $user->can('root:manage')
            || $user->can('export-for-billing:finances');
    }

    /**
     * Staff payment tools: manual ledger status updates and ledger/status lookups for any user.
     * Mirrors the sidebar gate for the payments debug tool.
     */
    public function managePaymentTools(User $user): bool
    {
        return $user->can('root:manage');
    }
}
