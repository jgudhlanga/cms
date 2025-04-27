<?php

namespace App\Http\Requests\Districts;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $discrict
 */
class DistrictRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:districts,name,'.$this->discrict?->id],
        ];
    }
}
