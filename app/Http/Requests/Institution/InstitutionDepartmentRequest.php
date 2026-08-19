<?php

namespace App\Http\Requests\Institution;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            return [
                'division_id' => ['nullable', 'integer', Rule::exists('divisions', 'id')],
                'department_code' => ['nullable', 'string', 'max:50'],
                'description' => ['nullable', 'string', 'max:1000'],
                'has_apprentice_courses' => ['sometimes', 'boolean'],
            ];
        }

        return [
            'is_academic' => ['required', 'boolean'],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['integer', 'exists:departments,id'],
        ];
    }
}
