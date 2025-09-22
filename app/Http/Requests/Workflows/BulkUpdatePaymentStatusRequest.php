<?php

namespace App\Http\Requests\Workflows;

use Illuminate\Foundation\Http\FormRequest;


class BulkUpdatePaymentStatusRequest extends FormRequest
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
            'mode_of_study_id' => ['required', 'integer', 'exists:mode_of_studies,id'],
            'field_to_update' => ['required', 'string'],
            'field_value' => ['required', 'bool'],
        ];
    }
}
