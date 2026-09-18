<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use Illuminate\Http\Request;

final class PaymentGatewayUnlock
{
    public function isUnlocked(Request $request): bool
    {
        $confirmedAt = (int) $request->session()->get(PaymentGatewayConfig::UNLOCK_SESSION_KEY, 0);

        return $confirmedAt > 0
            && (time() - $confirmedAt) < PaymentGatewayConfig::UNLOCK_TTL_SECONDS;
    }

    public function unlock(Request $request): void
    {
        $request->session()->put(PaymentGatewayConfig::UNLOCK_SESSION_KEY, time());
    }

    public function lock(Request $request): void
    {
        $request->session()->forget(PaymentGatewayConfig::UNLOCK_SESSION_KEY);
    }
}
