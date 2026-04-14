<?php

namespace App\Http\Requests\AcademicCalendars;

use Illuminate\Foundation\Http\FormRequest;

class AcademicYearOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:academic_year_options,name,'.$this->academic_year_option?->id],
            'description' => ['nullable', 'string'],
        ];
    }
}
