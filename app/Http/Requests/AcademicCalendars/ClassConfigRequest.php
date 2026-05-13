<?php

namespace App\Http\Requests\AcademicCalendars;

use Illuminate\Foundation\Http\FormRequest;

class ClassConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'students_per_class' => ['required', 'integer', 'min:1'],
            'department_level_id' => ['required', 'exists:department_levels,id'],
            'department_course_id' => ['required', 'exists:department_courses,id'],
            'mode_of_study_id' => ['required', 'exists:mode_of_studies,id'],
        ];
    }
}
