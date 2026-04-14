<?php

namespace App\Http\Requests\AcademicCalendars;

use Illuminate\Foundation\Http\FormRequest;

class AcademicCalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'calendar_year' => ['required'],
            'opening_date' => ['required', 'before:closing_date'],
            'closing_date' => ['required', 'after:opening_date'],
        ];
    }
}
