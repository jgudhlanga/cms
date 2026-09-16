<?php

declare(strict_types=1);

namespace App\Http\Requests\Students;

use App\Enums\Students\StudyPositionStateEnum;
use App\Models\Students\Student;
use App\Repositories\Students\StudentRepository;
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
            'student_type' => ['nullable', Rule::in(['direct', 'apprentice'])],
            'sponsored' => ['nullable', Rule::in(['sponsored', 'not_sponsored'])],
            'disability' => ['nullable', Rule::in(['yes', 'no'])],
            'study_position' => [
                'nullable',
                Rule::in([...array_column(StudyPositionStateEnum::cases(), 'value'), StudentRepository::STUDY_POSITION_ATTENTION]),
            ],
            'search' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
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
