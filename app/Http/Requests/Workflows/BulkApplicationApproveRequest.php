<?php

namespace App\Http\Requests\Workflows;

use Illuminate\Foundation\Http\FormRequest;


class BulkApplicationApproveRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'intake_period_id' => ['required', 'integer', 'exists:intake_periods,id'],
            'department_level_id' => ['required', 'integer', 'exists:department_levels,id'],
            'current_step_id' => ['required', 'integer', 'exists:department_application_steps,id'],
            'new_step_id' => ['required', 'integer', 'exists:department_application_steps,id'],
        ];
    }
}
