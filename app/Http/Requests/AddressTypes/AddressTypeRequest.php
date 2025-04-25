<?php

namespace App\Http\Requests\AddressTypes;

use Illuminate\Foundation\Http\FormRequest;

class AddressTypeRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', 'unique:address_types,title,' . $this->address_type?->id]
        ];
    }
}
