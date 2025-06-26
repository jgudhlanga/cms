<?php

namespace App\Http\Requests\Students;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $exam_results
 */
class AcademicRecordRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        if (is_string($this->exam_results)) {
            $this->merge([
                'exam_results' => json_decode($this->exam_results, true),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'school' => ['required', 'string', 'max:255'],
            'place' => ['required', 'string', 'max:255'],
        ];
    }
}
