<?php

namespace App\Http\Requests\Institution;

use Illuminate\Foundation\Http\FormRequest;

class IntakePeriodClassSizeRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        if (is_string($this->class_sizes)) {
            $this->merge([
                'class_sizes' => json_decode($this->class_sizes, true),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'intake_period_id' => ['required', 'exists:intake_periods,id'],
            'mode_of_study_id' => ['required', 'exists:mode_of_studies,id'],
            'class_sizes' => ['required', 'array'],
            'class_sizes.*.department_course_id' => 'required|exists:department_courses,id',
            'class_sizes.*.department_level_id' => 'required|exists:department_levels,id',
            'class_sizes.*.class_size' => 'nullable|integer|min:0',
        ];
    }
}
