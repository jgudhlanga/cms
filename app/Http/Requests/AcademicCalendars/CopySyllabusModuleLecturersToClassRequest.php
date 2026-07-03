<?php

namespace App\Http\Requests\AcademicCalendars;

use Illuminate\Foundation\Http\FormRequest;

class CopySyllabusModuleLecturersToClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_option_id' => ['required', 'integer', 'exists:academic_year_options,id'],
        ];
    }
}
