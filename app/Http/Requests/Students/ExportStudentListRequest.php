<?php

declare(strict_types=1);

namespace App\Http\Requests\Students;

use App\Models\Students\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportStudentListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('export', Student::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'department' => ['required', 'array', 'min:1'],
            'department.*' => ['integer', 'exists:institution_departments,id'],
            'level' => ['nullable', 'array'],
            'level.*' => ['integer'],
            'course' => ['nullable', 'array'],
            'course.*' => ['integer'],
            'mode_of_study' => ['nullable', 'array'],
            'mode_of_study.*' => ['integer'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'department.required' => __('students.export_department_required'),
            'department.min' => __('students.export_department_required'),
        ];
    }
}
