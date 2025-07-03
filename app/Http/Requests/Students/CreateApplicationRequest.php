<?php

namespace App\Http\Requests\Students;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $o_level_subject_ids
 */
class CreateApplicationRequest extends FormRequest
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
    }


    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'gender_id' => ['required', 'integer', 'exists:genders,id'],
            'marital_status_id' => ['required', 'integer', 'exists:marital_statuses,id'],
            'title_id' => ['required', 'integer', 'exists:titles,id'],
            'id_type' => ["required", "string"],
            'id_number' => ["required_if:id_type, zimbabwean-national-id-number"],
            'passport_number' => ["required_if:id_type, foreign-passport-number"],
            'country_id' => ["required_if:id_type, foreign-passport-number"],
            'address_1' => ['required', 'string', 'max:255'],
            'address_2' => ['required', 'string', 'max:255'],
            'address_3' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255', 'email', 'unique:users'],
            'phone_number' => ['required', 'string', 'max:30'],
            'next_of_kin_name' => ['required', 'string', 'max:255'],
            'next_of_kin_address_1' => ['required', 'string', 'max:255'],
            'next_of_kin_address_2' => ['required', 'string', 'max:255'],
            'next_of_kin_address_3' => ['required', 'string', 'max:255'],
            'relationship_id' => ['required', 'integer', 'exists:relationships,id'],
            'next_of_kin_phone_number' => ['required', 'string', 'max:30'],
            'department_id' => ['required', 'integer'],
            'level_id' => ['required', 'integer',],
            'course_id' => ['required', 'integer',],
            'o_level_subject_ids' => ['nullable', 'array'],
        ];
    }
}
