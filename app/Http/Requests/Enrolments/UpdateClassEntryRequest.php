<?php

namespace App\Http\Requests\Enrolments;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClassEntryRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'identity_confirmed' => ['required', 'boolean'],
            'disability_confirmed' => ['required', 'boolean'],
            'names_confirmed' => ['required', 'boolean'],
        ];
    }
}
