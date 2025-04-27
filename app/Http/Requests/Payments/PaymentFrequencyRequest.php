<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $payment_frequency
 */
class PaymentFrequencyRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'unique:payment_frequencies,title,' . $this->payment_frequency?->id],
        ];
    }
}
