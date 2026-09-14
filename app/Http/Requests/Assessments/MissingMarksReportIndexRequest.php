<?php

namespace App\Http\Requests\Assessments;

use Illuminate\Foundation\Http\FormRequest;

class MissingMarksReportIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view:missing-marks-report') ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'calendar_year' => ['nullable', 'integer'],
            'institution_department_id' => ['nullable', 'integer'],
            'level_id' => ['nullable', 'integer'],
            'course_id' => ['nullable', 'integer'],
            'module_id' => ['nullable', 'integer'],
            'lecturer_staff_id' => ['nullable', 'integer'],
            'assessment_type_id' => ['nullable', 'integer'],
        ];
    }
}
