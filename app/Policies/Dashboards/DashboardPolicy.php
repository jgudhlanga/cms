<?php

namespace App\Policies\Dashboards;

use App\Models\Users\User;

class DashboardPolicy
{
    public function viewDashboard(User $user): bool
    {
        return $user->can('viewAny:dashboards') || $user->can('view:dashboards');
    }
}
