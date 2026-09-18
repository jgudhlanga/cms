<?php

declare(strict_types=1);

namespace App\Http\Requests\Integrations;

use App\Models\Integrations\PaymentGatewaySetting;
use Illuminate\Foundation\Http\FormRequest;

class UnlockPaymentGatewayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view', PaymentGatewaySetting::class) === true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string'],
        ];
    }
}
