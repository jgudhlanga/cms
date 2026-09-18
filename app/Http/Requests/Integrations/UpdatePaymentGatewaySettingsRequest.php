<?php

declare(strict_types=1);

namespace App\Http\Requests\Integrations;

use App\Models\Integrations\PaymentGatewaySetting;
use App\Services\Integrations\PaymentGatewayUnlock;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentGatewaySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->can('update', PaymentGatewaySetting::class)
            && app(PaymentGatewayUnlock::class)->isUnlocked($this);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'gateway_name' => ['nullable', 'string', 'max:255'],
            'gateway_base_url' => ['nullable', 'string', 'max:2048'],
            'gateway_api_key' => ['nullable', 'string', 'max:2000'],
            'gateway_secret' => ['nullable', 'string', 'max:2000'],
            'bank_statements_base_url' => ['nullable', 'string', 'max:2048'],
            'usd_account_number' => ['nullable', 'string', 'max:255'],
            'usd_password' => ['nullable', 'string', 'max:2000'],
            'zwg_account_number' => ['nullable', 'string', 'max:255'],
            'zwg_password' => ['nullable', 'string', 'max:2000'],
            'income_gen_account_number' => ['nullable', 'string', 'max:255'],
            'income_gen_password' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
