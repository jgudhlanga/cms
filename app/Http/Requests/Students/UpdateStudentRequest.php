<?php

namespace App\Http\Requests\Students;

use App\Enums\Shared\IdTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $idType = IdTypeEnum::ZIMBABWEAN_ID_NUMBER->id();
        $passportType = IdTypeEnum::FOREIGN_PASSPORT_NUMBER->id();
        $studentId = $this->route('student')?->id ?? $this->route('student'); // support both model or raw ID
        return [
            'gender_id' => ['required', 'integer', 'exists:genders,id'],
            'marital_status_id' => ['required', 'integer', 'exists:marital_statuses,id'],
            'title_id' => ['required', 'integer', 'exists:titles,id'],
            'id_type_id' => ['required', 'integer', 'exists:id_types,id'],

            'id_number' => [
                'required_if:id_type_id,' . $idType,
                Rule::unique('applicants', 'id_number')->ignore($studentId),
            ],

            'passport_number' => [
                'required_if:id_type_id,' . $passportType,
                Rule::unique('applicants', 'passport_number')->ignore($studentId),
            ],
            'country_id' => ['required_if:id_type_id,' . $passportType, 'nullable', 'exists:countries,id'],
        ];
    }
}
