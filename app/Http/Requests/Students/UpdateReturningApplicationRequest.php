<?php

namespace App\Http\Requests\Students;

use App\Enums\Shared\IdTypeEnum;
use App\Rules\ZimbabweanIdNumber;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateReturningApplicationRequest extends CreateApplicationRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $studentId = $this->user()?->studentProfile?->id;
        $idType = IdTypeEnum::ZIMBABWEAN_ID_NUMBER->id();
        $passportType = IdTypeEnum::FOREIGN_PASSPORT_NUMBER->id();

        $rules['id_number'] = [
            'required_if:id_type_id,'.$idType,
            'nullable',
            'string',
            'max:20',
            new ZimbabweanIdNumber,
            Rule::unique('students', 'id_number')->ignore($studentId),
        ];

        $rules['passport_number'] = [
            'required_if:id_type_id,'.$passportType,
            'nullable',
            'string',
            'min:5',
            'max:50',
            Rule::unique('students', 'passport_number')->ignore($studentId),
        ];

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);
    }
}
