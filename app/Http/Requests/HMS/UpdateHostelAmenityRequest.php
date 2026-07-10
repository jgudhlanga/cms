<?php

namespace App\Http\Requests\HMS;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHostelAmenityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'market_value' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
