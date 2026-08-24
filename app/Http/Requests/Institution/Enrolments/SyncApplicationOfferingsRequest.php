<?php

declare(strict_types=1);

namespace App\Http\Requests\Institution\Enrolments;

use Illuminate\Foundation\Http\FormRequest;

class SyncApplicationOfferingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage:online-application-catalogue') ?? false;
    }

    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'has_apprentice_programmes' => ['required', 'boolean'],
            'levels' => ['nullable', 'array'],
            'levels.*.department_level_id' => ['required', 'integer', 'exists:department_levels,id'],
            'levels.*.courses' => ['nullable', 'array'],
            'levels.*.courses.*.department_course_id' => ['required', 'integer', 'exists:department_courses,id'],
            'levels.*.courses.*.mode_of_study_ids' => ['required', 'array', 'min:1'],
            'levels.*.courses.*.mode_of_study_ids.*' => ['integer', 'exists:mode_of_studies,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'levels.*.courses.*.mode_of_study_ids.required' => __('application_offerings.no_modes_selected'),
            'levels.*.courses.*.mode_of_study_ids.min' => __('application_offerings.no_modes_selected'),
        ];
    }
}
