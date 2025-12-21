<?php

namespace App\Http\Requests\AcademicCalendars;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * @property mixed $academic_calendar
 */
class AcademicCalendarRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('academic_calendars', 'name')
                    ->ignore($this->academic_calendar),
            ],
            'calendar_type' => ['required', new Enum(AcademicCalendarTypeEnum::class)],
            'calendar_year' => ['required', 'string'],
            'opening_date' => ['required', 'date'],
            'closing_date' => ['required', 'date', 'after:opening_date'],
        ];
    }
}
