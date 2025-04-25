<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $payment_method
 */
class PaymentMethodRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'unique:payment_methods,title,' . $this->payment_method?->id],
        ];
    }
}
