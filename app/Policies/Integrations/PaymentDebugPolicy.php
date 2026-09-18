<?php

declare(strict_types=1);

namespace App\Policies\Integrations;

use App\Enums\Shared\ModuleEnum;
use App\Models\Users\User;
use App\Services\Rbac\RbacModuleStateService;

class PaymentDebugPolicy
{
    public function __construct(
        private readonly RbacModuleStateService $moduleState,
    ) {}

    public function viewPaymentsDebug(User $user): bool
    {
        return $this->moduleEnabled()
            && (
                $user->can('view:payments-debug')
                || $user->can('update:payments-debug')
            );
    }

    public function updatePaymentsDebug(User $user): bool
    {
        return $this->moduleEnabled() && $user->can('update:payments-debug');
    }

    private function moduleEnabled(): bool
    {
        return $this->moduleState->isEnabled(ModuleEnum::INTEGRATIONS->slug());
    }
}
