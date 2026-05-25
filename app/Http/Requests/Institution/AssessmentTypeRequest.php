<?php

namespace App\Http\Requests\Institution;

use Illuminate\Foundation\Http\FormRequest;

class AssessmentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $assessmentTypeId = $this->assessment_type?->id;

        return [
            'name' => ['required', 'string', 'max:255', 'unique:assessment_types,name,'.$assessmentTypeId],
            'modes_of_study' => ['required', 'array', 'min:1'],
            'modes_of_study.*' => ['integer', 'exists:mode_of_studies,id'],
            'description' => ['nullable', 'string'],
        ];
    }
}
