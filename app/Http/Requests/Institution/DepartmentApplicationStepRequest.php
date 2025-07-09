<?php

namespace App\Http\Requests\Institution;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed|string $course_ids
 */
class DepartmentApplicationStepRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        if (is_string($this->workflow_step_ids)) {
            $this->merge([
                'workflow_step_ids' => json_decode($this->workflow_step_ids, true),
            ]);
        }
    }


    public function rules(): array
    {
        return [
            'workflow_step_ids' => ['nullable', 'array'],
            'workflow_step_ids.*' => ['integer', 'exists:workflow_steps,id'],
        ];
    }
}
