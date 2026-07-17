<?php

namespace App\Support\Auth;

use App\Models\Users\User;
use App\Services\Dashboard\DashboardModuleService;

class DefaultHome
{
    public static function routeName(User $user): string
    {
        $dashboardModuleService = app(DashboardModuleService::class);

        if (
            $user->can('view:lecturer-dashboard')
            && ! $dashboardModuleService->canAccessDashboard($user)
        ) {
            return 'lecturer.dashboard';
        }

        return 'dashboard';
    }

    public static function shouldUseLecturerHome(User $user): bool
    {
        return self::routeName($user) === 'lecturer.dashboard';
    }
}
