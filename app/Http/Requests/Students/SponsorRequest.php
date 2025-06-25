<?php

namespace App\Http\Requests\Students;

use Illuminate\Foundation\Http\FormRequest;

class SponsorRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:30'],
        ];
    }
}
