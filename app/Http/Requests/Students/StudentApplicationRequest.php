<?php

namespace App\Http\Requests\Students;

use Illuminate\Foundation\Http\FormRequest;

class StudentApplicationRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'institution_department_id' => ['required', 'integer', 'exists:institution_departments,id'],
            'department_level_id' => ['required', 'integer', 'exists:department_levels,id'],
            'department_course_id' => ['required', 'integer', 'exists:department_courses,id'],
        ];
    }
}
