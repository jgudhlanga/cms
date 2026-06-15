<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class UpdateUserCredentialsRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        $emailRules = [
            'nullable',
            'email',
            'max:255',
            Rule::unique('users', 'email')->ignore($this->user->id),
        ];

        if ($this->boolean('change_email')) {
            $emailRules[] = 'required';
        }

        $passwordRules = [
            'nullable',
            'confirmed',
            Rules\Password::defaults(),
        ];

        if ($this->boolean('change_password')) {
            $passwordRules[] = 'required';
        }

        return [
            'change_email' => ['nullable', 'boolean'],
            'change_password' => ['nullable', 'boolean'],
            'email' => $emailRules,
            'password' => $passwordRules,
        ];
    }
}
