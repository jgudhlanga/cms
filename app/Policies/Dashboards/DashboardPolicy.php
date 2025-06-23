<?php

namespace App\Policies\Dashboards;

use App\Enums\Shared\PermissionEnum;
use App\Models\Users\User;

class DashboardPolicy
{
    public function viewDashboard(User $user): bool
    {
        return $user->can(PermissionEnum::VIEW_ANY_DASHBOARD)
            || $user->can(PermissionEnum::VIEW_DASHBOARD);
    }
}
