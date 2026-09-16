<?php

declare(strict_types=1);

namespace App\Http\Requests\Students;

use App\Enums\Students\StudyPositionAnswerEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmOwnStudyPositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('confirmOwnStudyPosition') ?? false;
    }

    /**
     * Which enrolments the student may answer for is checked against their own scope in the
     * controller, so a forged id gets the same generic refusal as an out-of-scope one.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1', 'max:10'],
            'answers.*.student_enrolment_id' => ['required', 'integer', 'distinct'],
            'answers.*.answer' => ['required', Rule::enum(StudyPositionAnswerEnum::class)],
            'answers.*.programme_semester_id' => [
                'nullable',
                'integer',
                'required_if:answers.*.answer,'.StudyPositionAnswerEnum::PHASE->value,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'answers.*.answer.required' => __('students.study_position_answer_required'),
            'answers.*.programme_semester_id.required_if' => __('students.study_position_answer_required'),
        ];
    }
}
