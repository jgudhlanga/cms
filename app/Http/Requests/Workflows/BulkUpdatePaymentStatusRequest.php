<?php

namespace App\Http\Requests\Workflows;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'current_step_id' => ['required', 'integer', 'exists:workflow_steps,id'],
            'mode_of_study_id' => ['required', 'integer', 'exists:mode_of_studies,id'],
            'field_to_update' => ['required', 'string', Rule::in(['registration_fee_confirmed', 'tuition_fee_confirmed'])],
            'field_value' => ['required', 'bool'],
        ];
    }
}
