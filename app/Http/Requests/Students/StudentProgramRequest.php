<?php

namespace App\Http\Requests\Students;

use Illuminate\Foundation\Http\FormRequest;

class StudentProgramRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'level_id' => ['required', 'integer', 'exists:levels,id'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
        ];
    }
}
