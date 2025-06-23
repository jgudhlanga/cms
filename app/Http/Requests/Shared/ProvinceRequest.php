<?php

namespace App\Http\Requests\Shared;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $province
 */
class ProvinceRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', 'unique:provinces,title,' . $this->province?->id]
        ];
    }
}
