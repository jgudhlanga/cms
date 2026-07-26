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
    }

    public function rules(): array
    {
        return [
            'department_level_ids' => ['nullable', 'array'],
            'show_on_current_application_period' => ['sometimes', 'boolean'],
            'coursework_capture_enabled' => ['sometimes', 'nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->has('coursework_capture_enabled')) {
                return;
            }

            if (! $this->user()?->can('toggle:coursework-capture')) {
                $validator->errors()->add(
                    'coursework_capture_enabled',
                    __('trans.unauthorized'),
                );
            }
        });
    }
}
