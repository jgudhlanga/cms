<?php

namespace App\Http\Requests\Assessments;

use Illuminate\Foundation\Http\FormRequest;

class MissingMarksRemindRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('remind:missing-marks') ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'assessment_calendar_id' => ['required', 'integer', 'exists:assessment_calendars,id'],
        ];
    }
}
