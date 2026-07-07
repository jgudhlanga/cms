<?php

namespace App\Http\Requests\Institution;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssessmentCalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_calendar_id' => ['required', 'integer', 'exists:academic_calendars,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'type' => ['required', Rule::enum(AcademicCalendarTypeEnum::class)],
        ];
    }
}
