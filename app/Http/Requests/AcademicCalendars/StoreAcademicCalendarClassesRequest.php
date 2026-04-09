<?php

namespace App\Http\Requests\AcademicCalendars;

use Illuminate\Foundation\Http\FormRequest;

class StoreAcademicCalendarClassesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'class_config_id' => ['required', 'integer', 'exists:class_configs,id'],
            'department_level_id' => ['required', 'integer', 'exists:department_levels,id'],
            'department_course_id' => ['required', 'integer', 'exists:department_courses,id'],
            'mode_of_study_id' => ['required', 'integer', 'exists:mode_of_studies,id'],
            'students_per_class' => ['required', 'integer', 'min:1'],
        ];
    }
}
