<?php

namespace App\Http\Requests\Shared;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
			'name' => ['required'],
			'phone_number' => ['required'],
			'email_address' => ['required', 'email'],
        ];
    }
}
