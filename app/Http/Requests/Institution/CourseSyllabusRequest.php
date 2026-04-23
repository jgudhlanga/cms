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
        $activeStatus = CourseSyllabusStatusEnum::Active->value;
        $departmentLevelCourseRules = ['required', 'exists:department_level_courses,id'];

        if ($this->string('status')->toString() === $activeStatus) {
            $departmentLevelCourseRules[] = Rule::unique('course_syllabuses', 'department_level_course_id')
                ->ignore($courseSyllabusId)
                ->where(function ($query) use ($activeStatus) {
                    $query
                        ->where('institution_department_id', $this->integer('institution_department_id'))
                        ->where('status', $activeStatus);
                });
        }

        return [
            'institution_department_id' => ['required', 'exists:institution_departments,id'],
            'department_level_course_id' => $departmentLevelCourseRules,
            'title' => ['required', 'string', 'max:255', Rule::unique('course_syllabuses', 'title')->ignore($courseSyllabusId)],
            'code' => ['required', 'string', 'max:255', Rule::unique('course_syllabuses', 'code')->ignore($courseSyllabusId)],
            'implementation_year' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(CourseSyllabusStatusEnum::class)],
            'syllabus_document' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'department_level_course_id.unique' => 'An active syllabus already exists for this department, level and course. Please update the existing syllabus instead.',
        ];
    }
}
