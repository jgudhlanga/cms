<?php

namespace App\Http\Requests\Students;

use App\Enums\Shared\IdTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user'); // The User model bound to the route
        $userId = $user?->id;
        $studentId = $user?->studentProfile?->id;

        $idType = IdTypeEnum::ZIMBABWEAN_ID_NUMBER->id();
        $passportType = IdTypeEnum::FOREIGN_PASSPORT_NUMBER->id();

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'gender_id' => ['required', 'integer', 'exists:genders,id'],
            'marital_status_id' => ['required', 'integer', 'exists:marital_statuses,id'],
            'title_id' => ['required', 'integer', 'exists:titles,id'],
            'id_type_id' => ['required', 'integer', 'exists:id_types,id'],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],

            'phone_number' => [
                'required',
                'string',
                'max:30',
                Rule::unique('users', 'phone_number')->ignore($userId),
            ],

            'id_number' => [
                'nullable',
                'required_if:id_type_id,' . $idType,
                Rule::unique('students', 'id_number')->ignore($studentId),
            ],

            'passport_number' => [
                'nullable',
                'required_if:id_type_id,' . $passportType,
                Rule::unique('students', 'passport_number')->ignore($studentId),
            ],

            'country_id' => [
                'nullable',
                'required_if:id_type_id,' . $passportType,
                'exists:countries,id',
            ],
        ];
    }
}
