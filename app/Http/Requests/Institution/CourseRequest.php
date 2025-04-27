<?php

namespace App\Http\Requests\Institution;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $course
 */
class CourseRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:courses,name,'.$this->course?->id],
        ];
    }
}
