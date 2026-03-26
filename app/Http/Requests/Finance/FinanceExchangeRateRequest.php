<?php

namespace App\Http\Requests\Finance;

use App\Rules\ValidExchangeRateDate;
use App\Rules\ValidExchangeRateRate;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $exchange_rate
 */
class FinanceExchangeRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', new ValidExchangeRateDate],
            'currency_from' => ['required', 'string', 'max:255'],
            'currency_to' => ['required', 'string', 'max:255'],
            'rate' => ['required', new ValidExchangeRateRate],
        ];
    }
}
