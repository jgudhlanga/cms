<?php

namespace App\Http\Requests\Institution;

use App\Enums\Institution\CourseSyllabusStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseSyllabusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $courseSyllabusId = (int) ($this->route('course_syllabus')?->id ?? 0);

        return [
            'institution_department_id' => ['required', 'exists:institution_departments,id'],
            'department_level_course_id' => ['required', 'exists:department_level_courses,id'],
            'title' => ['required', 'string', 'max:255', Rule::unique('course_syllabuses', 'title')->ignore($courseSyllabusId)],
            'code' => ['required', 'string', 'max:255', Rule::unique('course_syllabuses', 'code')->ignore($courseSyllabusId)],
            'implementation_year' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(CourseSyllabusStatusEnum::class)],
            'syllabus_document' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ];
    }
}
