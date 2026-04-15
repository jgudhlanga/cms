<?php

namespace App\Http\Requests\Preferences;

use Illuminate\Foundation\Http\FormRequest;

class UserPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'side_bar_state' => ['required', 'boolean'],
        ];
    }
}
