<?php

namespace App\Http\Requests\Institution;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $main_subject_ids
 */
class DepartmentLevelRequirementRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        if (is_string($this->main_subject_ids)) {
            $this->merge([
                'main_subject_ids' => json_decode($this->main_subject_ids, true),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'main_subject_ids' => ['nullable', 'array'],
            'main_subject_ids.*' => ['integer', 'exists:subjects,id'],
        ];
    }
}
