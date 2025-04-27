<?php

namespace App\Http\Requests\Countries;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $country
 */
class CountryRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', "unique:countries,name," . $this->country?->id],
        ];
    }
}
