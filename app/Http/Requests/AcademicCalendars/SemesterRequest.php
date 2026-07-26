<?php

namespace App\Http\Requests\AcademicCalendars;

use Illuminate\Foundation\Http\FormRequest;

class SemesterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:semesters,name,'.$this->semester?->id],
            'description' => ['nullable', 'string'],
        ];
    }
}
