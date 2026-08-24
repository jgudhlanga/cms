<?php

namespace App\Http\Requests\Institution;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIntakeClassSizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('department-setup:class-sizes');
    }

    public function rules(): array
    {
        return [
            'intake_period_id' => ['required', 'exists:intake_periods,id'],
            'mode_of_study_id' => ['required', 'exists:mode_of_studies,id'],
            'department_course_id' => ['required', 'exists:department_courses,id'],
            'department_level_id' => ['required', 'exists:department_levels,id'],
            'class_size' => ['required', 'integer', 'min:0'],
        ];
    }
}
