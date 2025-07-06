<?php

namespace App\Http\Requests\Institution;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed|string $department_level_ids
 */
class DepartmentApplicationStepUpdateRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function prepareForValidation(): void
    {
        if (is_string($this->role_ids)) {
            $this->merge([
                'role_ids' => json_decode($this->role_ids, true),
            ]);
        }
        if (is_string($this->staff_ids)) {
            $this->merge([
                'staff_ids' => json_decode($this->staff_ids, true),
            ]);
        }
    }


    public function rules(): array
    {
        return [
            'role_ids' => ['nullable', 'array'],
            'staff_ids' => ['nullable', 'array'],
        ];
    }
}
