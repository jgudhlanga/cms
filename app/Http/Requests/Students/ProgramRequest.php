<?php

namespace App\Http\Requests\Students;

use App\Rules\Students\ValidateOLevelResults;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * @property mixed $o_level_subject_ids
 * @property mixed $o_level_years
 * @property mixed $o_level_sittings
 * @property mixed $o_level_other_subject_ids
 * @property mixed $o_level_other_grade_ids
 * @property mixed $o_level_other_years
 * @property mixed $o_level_other_sittings
 */
class ProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        if (is_string($this->o_level_subject_ids)) {
            $this->merge([
                'o_level_subject_ids' => json_decode($this->o_level_subject_ids, true),
            ]);
        }
        if (is_string($this->o_level_years)) {
            $this->merge([
                'o_level_years' => json_decode($this->o_level_years, true),
            ]);
        }
        if (is_string($this->o_level_sittings)) {
            $this->merge([
                'o_level_sittings' => json_decode($this->o_level_sittings, true),
            ]);
        }
        if (is_string($this->o_level_other_subject_ids)) {
            $this->merge([
                'o_level_other_subject_ids' => json_decode($this->o_level_other_subject_ids, true),
            ]);
        }
        if (is_string($this->o_level_other_grade_ids)) {
            $this->merge([
                'o_level_other_grade_ids' => json_decode($this->o_level_other_grade_ids, true),
            ]);
        }
        if (is_string($this->o_level_other_years)) {
            $this->merge([
                'o_level_other_years' => json_decode($this->o_level_other_years, true),
            ]);
        }
        if (is_string($this->o_level_other_sittings)) {
            $this->merge([
                'o_level_other_sittings' => json_decode($this->o_level_other_sittings, true),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'department_id' => ['required', 'integer'],
            'level_id' => ['required', 'integer'],
            'course_id' => ['required', 'integer'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            app(ValidateOLevelResults::class)->validate($this, $validator);
        });
    }
}
