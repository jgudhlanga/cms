<?php

namespace App\Http\Requests\AcademicCalendars;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

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
            'type' => ['required', new Enum(AcademicCalendarTypeEnum::class)],
            'opening_date' => ['required', 'before:closing_date'],
            'closing_date' => ['required', 'after:opening_date'],
        ];
    }
}
