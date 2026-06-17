<?php

namespace App\Services\Dashboard;

use App\Helpers\RolePriorityHelper;
use App\Models\Users\User;
use App\Services\Acl\AclModuleStateService;
use App\Support\Dashboard\DashboardTab;

class DashboardModuleService
{
    private const string MODULE_SLUG = 'dashboards';

    public function __construct(private AclModuleStateService $aclModuleState) {}

    public function clearCache(): void
    {
        $this->aclModuleState->clearCache();
    }

    public function isEnabled(): bool
    {
        return $this->aclModuleState->isEnabled(self::MODULE_SLUG);
    }

    /**
     * @return array<string, bool>
     */
    public function enabledTabs(): array
    {
        $defaults = DashboardTab::defaultTabSettings();
        $stored = $this->aclModuleState->settingsFor(self::MODULE_SLUG)['tabs'] ?? [];

        return array_merge($defaults, array_intersect_key($stored, $defaults));
    }

    /**
     * @return list<string>
     */
    public function visibleTabsFor(User $user): array
    {
        if (! $this->isEnabled()) {
            return [];
        }

        $visible = [];

        foreach (DashboardTab::cases() as $tab) {
            if (! ($this->enabledTabs()[$tab->value] ?? false)) {
                continue;
            }

            if ($user->can('viewAny:dashboards') || $user->can($tab->permission())) {
                $visible[] = $tab->value;
            }
        }

        return $visible;
    }

    public function canAccessDashboard(User $user): bool
    {
        return $this->isEnabled() && $this->visibleTabsFor($user) !== [];
    }

    public function dashboardTitleFor(User $user): string
    {
        $roleName = RolePriorityHelper::resolvePrimaryRoleName($user);

        return __('dashboard.role_dashboard', ['role' => $roleName]);
    }
}
