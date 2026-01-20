<?php

namespace App\Http\Requests\Institution;

use Illuminate\Foundation\Http\FormRequest;


/**
 * @property mixed $mode_ids
 */
class CourseLevelModeRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function prepareForValidation(): void
    {
        if (is_string($this->mode_ids)) {
            $this->merge([
                'mode_ids' => json_decode($this->mode_ids, true),
            ]);
        }
    }


    public function rules(): array
    {
        return [
            'department_course_id' => ['required', 'exists:department_courses,id'],
            'mode_ids' => ['required','array'],
            'mode_ids.*' => ['array'],       // each level array
            'mode_ids.*.*' => ['integer'],   // mode ids
        ];
    }
}
