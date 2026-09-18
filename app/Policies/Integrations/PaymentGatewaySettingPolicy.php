<?php

declare(strict_types=1);

namespace App\Policies\Integrations;

use App\Enums\Shared\ModuleEnum;
use App\Models\Integrations\PaymentGatewaySetting;
use App\Models\Users\User;
use App\Services\Rbac\RbacModuleStateService;

class PaymentGatewaySettingPolicy
{
    public function __construct(
        private readonly RbacModuleStateService $moduleState,
    ) {}

    public function view(User $user, ?PaymentGatewaySetting $paymentGatewaySetting = null): bool
    {
        return $this->moduleEnabled()
            && (
                $user->can('view:payment-gateway-settings')
                || $user->can('update:payment-gateway-settings')
            );
    }

    public function update(User $user, ?PaymentGatewaySetting $paymentGatewaySetting = null): bool
    {
        return $this->moduleEnabled() && $user->can('update:payment-gateway-settings');
    }

    private function moduleEnabled(): bool
    {
        return $this->moduleState->isEnabled(ModuleEnum::INTEGRATIONS->slug());
    }
}
