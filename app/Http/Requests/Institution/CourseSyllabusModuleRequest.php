<?php

namespace App\Http\Requests\Institution;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseSyllabusModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $moduleId = (int) ($this->route('course_syllabus_module')?->id ?? 0);

        return [
            'course_syllabus_id' => ['required', 'exists:course_syllabuses,id'],
            'title' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', Rule::unique('course_syllabus_modules', 'code')->ignore($moduleId)],
            'duration_in_hours' => ['nullable', 'integer', 'min:1'],
            'nql_level' => ['nullable', 'integer', 'min:1'],
            'prerequisite_module_ids' => ['nullable', 'array'],
            'prerequisite_module_ids.*' => ['integer', 'distinct', 'exists:course_syllabus_modules,id'],
            'shared' => ['nullable', 'boolean'],
        ];
    }
}
