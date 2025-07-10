<?php

namespace App\Http\Requests\Institution;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $department_ids
 */
class InstitutionDepartmentRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        if (is_string($this->department_ids)) {
            $this->merge([
                'department_ids' => json_decode($this->department_ids, true),
            ]);
        }
    }


    public function rules(): array
    {
        return [
            'is_academic' => ['required', 'boolean'],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['integer', 'exists:departments,id'],
        ];
    }
}
