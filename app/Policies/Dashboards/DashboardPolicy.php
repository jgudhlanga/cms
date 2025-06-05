<?php

namespace App\Policies\Dashboards;

use App\Enums\PermissionEnum;
use App\Models\Shared\Address;
use App\Models\Users\User;

class DashboardPolicy
{
    public function viewDashboard(User $user): bool
    {
        return $user->can(PermissionEnum::VIEW_ANY_DASHBOARD)
            || $user->can(PermissionEnum::VIEW_DASHBOARD);
    }
}
