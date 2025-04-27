<?php

namespace App\Http\Requests\Genders;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $gender
 */
class GenderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', 'unique:genders,title,'.$this->gender?->id],
        ];
    }
}
