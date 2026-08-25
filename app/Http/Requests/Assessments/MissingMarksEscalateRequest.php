<?php

namespace App\Http\Requests\Assessments;

use Illuminate\Foundation\Http\FormRequest;

class MissingMarksEscalateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('escalate:missing-marks') ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'assessment_calendar_id' => ['required', 'integer', 'exists:assessment_calendars,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
