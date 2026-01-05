<?php

namespace App\Http\Requests\Institution;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed|string $department_level_ids
 * @property mixed $course_mode_ids
 */
class DepartmentCourseUpdateRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function prepareForValidation(): void
    {
        if (is_string($this->department_level_ids)) {
            $this->merge([
                'department_level_ids' => json_decode($this->department_level_ids, true),
            ]);
        }
        if (is_string($this->course_mode_ids)) {
            $this->merge([
                'course_mode_ids' => json_decode($this->course_mode_ids, true),
            ]);
        }
    }


    public function rules(): array
    {
        return [
            'department_level_ids' => ['nullable', 'array'],
            'course_mode_ids' => ['nullable', 'array'],
        ];
    }
}
