<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class UpdateUserCredentialsRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $this->user->id],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
    }
}
