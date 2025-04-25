<?php

namespace App\Http\Requests\Shared;

use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
			'address_1' => ['required'],
			'address_2' => ['required'],
			'address_3' => ['required'],
        ];
    }
}
