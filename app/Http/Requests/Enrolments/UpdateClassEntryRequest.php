<?php

namespace App\Http\Requests\Enrolments;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClassEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        $type = (string) $this->input('type', 'provisional');

        return match ($type) {
            'provisional', 'waiting' => $user->can('verify:class-lists'),
            'verified' => $user->can('confirm:class-lists'),
            default => false,
        };
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
