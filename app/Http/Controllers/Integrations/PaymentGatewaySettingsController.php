<?php

declare(strict_types=1);

namespace App\Http\Controllers\Integrations;

use App\Actions\Integrations\UpdatePaymentGatewaySettingsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Integrations\UnlockPaymentGatewayRequest;
use App\Http\Requests\Integrations\UpdatePaymentGatewaySettingsRequest;
use App\Models\Integrations\PaymentGatewaySetting;
use App\Services\Integrations\PaymentGatewayConfig;
use App\Services\Integrations\PaymentGatewayUnlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PaymentGatewaySettingsController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayConfig $config,
        private readonly PaymentGatewayUnlock $unlock,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('view', PaymentGatewaySetting::class);

        return Inertia::render('integrations/payment-gateway/Index', $this->pageProps($request));
    }

    public function update(
        UpdatePaymentGatewaySettingsRequest $request,
        UpdatePaymentGatewaySettingsAction $action,
    ): Response {
        $action->execute($request, $request->user());
        $this->unlock->unlock($request);

        return Inertia::render(
            'integrations/payment-gateway/Index',
            $this->pageProps($request, forceUnlocked: true),
        )->with('success', __('integrations.saved'));
    }

    public function unlock(UnlockPaymentGatewayRequest $request): JsonResponse
    {
        if (! Auth::guard('web')->validate([
            'email' => $request->user()->email,
            'password' => $request->string('password')->toString(),
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $this->unlock->unlock($request);

        return response()->json(['unlocked' => true]);
    }

    public function lock(Request $request): JsonResponse
    {
        $this->authorize('view', PaymentGatewaySetting::class);

        $this->unlock->lock($request);

        return response()->json(['locked' => true]);
    }

    public function touch(Request $request): JsonResponse
    {
        $this->authorize('view', PaymentGatewaySetting::class);

        if ($this->unlock->isUnlocked($request)) {
            $this->unlock->unlock($request);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Full GETs (including sidebar prefetch) stay locked and never decrypt secrets.
     * Unlock/save responses and `only=gateway` partial reloads may reveal the form.
     *
     * @return array<string, mixed>
     */
    private function pageProps(Request $request, bool $forceUnlocked = false): array
    {
        $unlocked = $forceUnlocked || $this->shouldRevealGateway($request);

        return [
            'locked' => ! $unlocked,
            'canUpdate' => $request->user()?->can('update', PaymentGatewaySetting::class) ?? false,
            'gateway' => $unlocked ? $this->config->forDisplay() : null,
        ];
    }

    private function shouldRevealGateway(Request $request): bool
    {
        if (! $this->unlock->isUnlocked($request)) {
            return false;
        }

        $partial = $request->header('X-Inertia-Partial-Data');

        if (! is_string($partial) || $partial === '') {
            return false;
        }

        $requested = array_map(trim(...), explode(',', $partial));

        return in_array('gateway', $requested, true);
    }
}
