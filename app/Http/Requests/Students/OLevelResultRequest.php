<?php

namespace App\Http\Requests\Students;

use Illuminate\Foundation\Http\FormRequest;

class OLevelResultRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'exam_year' => ['required', 'string'],
            'exam_sitting' => ['required', 'string'],
            'grade_id' => ['required', 'integer', 'exists:grades,id'],
        ];
    }
}
