<?php

namespace App\Policies\Dashboards;

use App\Models\Users\User;
use App\Services\Dashboard\DashboardModuleService;
use App\Support\Dashboard\DashboardTab;

class DashboardPolicy
{
    public function __construct(protected DashboardModuleService $dashboardModuleService) {}

    public function viewDashboard(User $user): bool
    {
        return $this->dashboardModuleService->canAccessDashboard($user);
    }

    public function viewTab(User $user, string $tab): bool
    {
        if (! $this->dashboardModuleService->isEnabled()) {
            return false;
        }

        $dashboardTab = DashboardTab::tryFrom($tab);

        if ($dashboardTab === null) {
            return false;
        }

        if (! ($this->dashboardModuleService->enabledTabs()[$dashboardTab->value] ?? false)) {
            return false;
        }

        return $user->can('viewAny:dashboards') || $user->can($dashboardTab->permission());
    }
}
