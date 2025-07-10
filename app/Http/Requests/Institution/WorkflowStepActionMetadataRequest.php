<?php

namespace App\Http\Requests\Institution;

use Illuminate\Foundation\Http\FormRequest;

class WorkflowStepActionMetadataRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function prepareForValidation(): void
    {
        if (is_string($this->workflow_action_ids)) {
            $this->merge([
                'workflow_action_ids' => json_decode($this->workflow_action_ids, true),
            ]);
        }
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
            'department_application_step_id' => ['required', 'integer', 'exists:department_application_steps,id'],
            'workflow_action_ids' => ['nullable', 'array'],
            'workflow_action_ids.*' => ['integer', 'exists:workflow_step_actions,id'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
            'staff_ids' => ['nullable', 'array'],
            'staff_ids.*' => ['integer', 'exists:staff,id'],
        ];
    }
}
