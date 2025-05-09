<?php

namespace App\Http\Requests\Institution;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed|string $course_ids
 */
class DepartmentCourseRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        if (is_string($this->course_ids)) {
            $this->merge([
                'course_ids' => json_decode($this->course_ids, true),
            ]);
        }
    }


    public function rules(): array
    {
        return [
            'course_ids' => ['nullable', 'array'],
            'course_ids.*' => ['integer', 'exists:courses,id'],
        ];
    }
}
