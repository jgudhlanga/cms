<?php

namespace App\Http\Requests\AcademicCalendars;

use Illuminate\Foundation\Http\FormRequest;

class AcademicCalendarRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function prepareForValidation(): void
    {
        if (is_string($this->intake_period_ids)) {
            $this->merge([
                'intake_period_ids' => json_decode($this->intake_period_ids, true),
            ]);
        }
    }


    public function rules(): array
    {
        return [
            'academic_calendar_option_id' => ['required', 'exists:academic_calendar_options,id'],
            'calendar_year' => ['required'],
            'opening_date' => ['required', 'before:closing_date'],
            'closing_date' => ['required', 'after:opening_date'],
            'intake_period_ids' => ['nullable', 'array'],
            'intake_period_ids.*' => ['nullable', 'integer', 'exists:intake_periods,id'],
        ];
    }
}
