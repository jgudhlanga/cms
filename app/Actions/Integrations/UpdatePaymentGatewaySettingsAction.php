<?php

declare(strict_types=1);

namespace App\Actions\Integrations;

use App\Http\Requests\Integrations\UpdatePaymentGatewaySettingsRequest;
use App\Models\Integrations\PaymentGatewaySetting;
use App\Models\Users\User;
use App\Services\Integrations\PaymentGatewayConfig;

final class UpdatePaymentGatewaySettingsAction
{
    public function __construct(
        private readonly PaymentGatewayConfig $config,
    ) {}

    public function execute(UpdatePaymentGatewaySettingsRequest $request, User $user): PaymentGatewaySetting
    {
        $settings = PaymentGatewaySetting::query()->firstOrCreate([]);
        $attributes = [];

        foreach (PaymentGatewaySetting::PUBLIC_ATTRIBUTES as $attribute) {
            $attributes[$attribute] = $this->nullableTrim($request->input($attribute));
        }

        foreach (PaymentGatewaySetting::SECRET_ATTRIBUTES as $attribute) {
            $secret = $this->nullableTrim($request->input($attribute));

            if ($secret !== null) {
                $attributes[$attribute] = $secret;
            }
        }

        $attributes['updated_by'] = $user->id;
        $settings->fill($attributes);
        $settings->save();

        $this->config->forget();

        return $settings;
    }

    private function nullableTrim(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
