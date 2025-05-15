<?php

namespace App\Http\Requests\Institution;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed|string $department_leve_ids
 */
class DepartmentCourseUpdateRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function prepareForValidation(): void
    {
        if (is_string($this->department_leve_ids)) {
            $this->merge([
                'department_leve_ids' => json_decode($this->department_leve_ids, true),
            ]);
        }
    }


    public function rules(): array
    {
        return [
            'department_leve_ids' => ['nullable', 'array'],
            'department_leve_ids.*' => ['integer', 'exists:department_levels,id'],
        ];
    }
}
