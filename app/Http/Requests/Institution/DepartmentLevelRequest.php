<?php

namespace App\Http\Requests\Institution;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $level_ids
 */
class DepartmentLevelRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function prepareForValidation(): void
    {
        if (is_string($this->level_ids)) {
            $this->merge([
                'level_ids' => json_decode($this->level_ids, true),
            ]);
        }
    }


    public function rules(): array
    {
        return [
            'level_ids' => ['nullable', 'array'],
            'level_ids.*' => ['integer', 'exists:levels,id'],
        ];
    }
}
