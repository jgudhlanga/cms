<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $payment_day
 */
class PaymentDayRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'unique:payment_days,title,' . $this->payment_day?->id],
        ];
    }
}
