<?php

namespace App\Http\Requests\Enrolments;

use App\Enums\Shared\ClassListTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class AddToClassListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create:class-lists') ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', new Enum(ClassListTypeEnum::class)],
            'note' => ['nullable', 'string', 'max:1000'],
            'bypass_ranking' => ['sometimes', 'boolean'],
            'institution_department_id' => ['nullable', 'integer', 'exists:institution_departments,id'],
            'department_level_id' => ['nullable', 'integer', 'exists:department_levels,id'],
            'department_course_id' => ['nullable', 'integer', 'exists:department_courses,id'],
            'intake_period_id' => ['nullable', 'integer', 'exists:intake_periods,id'],
            'mode_of_study_id' => ['nullable', 'integer', 'exists:mode_of_studies,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->boolean('bypass_ranking') && strlen(trim((string) $this->input('note', ''))) < 10) {
                $validator->errors()->add(
                    'note',
                    'A note of at least 10 characters is required when bypassing ranking.',
                );
            }
        });
    }

    /**
     * @return array{institution_department_id: int|null, department_level_id: int|null, department_course_id: int|null, intake_period_id: int|null, mode_of_study_id: int|null}
     */
    public function context(): array
    {
        return [
            'institution_department_id' => $this->integer('institution_department_id') ?: null,
            'department_level_id' => $this->integer('department_level_id') ?: null,
            'department_course_id' => $this->integer('department_course_id') ?: null,
            'intake_period_id' => $this->integer('intake_period_id') ?: null,
            'mode_of_study_id' => $this->integer('mode_of_study_id') ?: null,
        ];
    }
}
