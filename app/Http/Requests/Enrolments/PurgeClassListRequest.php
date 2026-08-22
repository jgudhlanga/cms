<?php

namespace App\Http\Requests\Enrolments;

use Illuminate\Foundation\Http\FormRequest;

class PurgeClassListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('delete:class-lists') ?? false;
    }

    public function rules(): array
    {
        return [
            'application_ids' => ['required', 'array', 'min:1'],
            'application_ids.*' => ['required', 'integer', 'exists:student_applications,id'],
            'note' => ['required', 'string', 'min:10', 'max:1000'],
            'institution_department_id' => ['nullable', 'integer', 'exists:institution_departments,id'],
            'department_level_id' => ['nullable', 'integer', 'exists:department_levels,id'],
            'department_course_id' => ['nullable', 'integer', 'exists:department_courses,id'],
            'intake_period_id' => ['nullable', 'integer', 'exists:intake_periods,id'],
            'mode_of_study_id' => ['nullable', 'integer', 'exists:mode_of_studies,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => 'A note explaining the removal is required.',
            'note.min' => 'The note must be at least 10 characters.',
        ];
    }

    /**
     * @return array{institution_department_id: int|null, department_level_id: int|null, department_course_id: int|null, intake_period_id: int|null, mode_of_study_id: int|null}
     */
    public function context(): array
    {
        return [
            'institution_department_id' => $this->integer('institution_department_id') ?: null,
            'department_level_id' => $this->integer('department_level_id') ?: null,
            'department_course_id' => $this->integer('department_course_id') ?: null,
            'intake_period_id' => $this->integer('intake_period_id') ?: null,
            'mode_of_study_id' => $this->integer('mode_of_study_id') ?: null,
        ];
    }
}
