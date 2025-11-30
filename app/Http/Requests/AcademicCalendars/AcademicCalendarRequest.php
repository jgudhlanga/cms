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
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', new Enum(AcademicCalendarTypeEnum::class)],
            'year' => ['required', 'string', 'max:4'],
            'opening_date' => ['required', 'date'],
            'closing_date' => ['required', 'date', 'after_or_equal:opening_date'],
            'description' => ['nullable', 'string'],
        ];
    }
}
