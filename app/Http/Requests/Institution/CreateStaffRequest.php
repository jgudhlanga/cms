<?php

namespace App\Http\Requests\Institution;

use Illuminate\Foundation\Http\FormRequest;

class CreateStaffRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        if (is_string($this->role_ids)) {
            $this->merge([
                'role_ids' => json_decode($this->role_ids, true),
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
            'email' => ['required', 'string', 'max:255', 'email', 'unique:users'],
            'role_ids' => ['nullable', 'array'],
            'institution_department_id' => ['nullable', 'integer', 'exists:institution_departments,id'],
        ];
    }
}
