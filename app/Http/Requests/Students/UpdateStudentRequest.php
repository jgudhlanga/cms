<?php

namespace App\Http\Requests\Students;

use App\Enums\Shared\DisabilityStatusEnum;
use App\Enums\Shared\IdTypeEnum;
use App\Rules\ZimbabweanIdNumber;
use App\Services\Enrollment\EnrollmentLookupService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        if ($this->filled('id_number')) {
            $this->merge([
                'id_number' => EnrollmentLookupService::normalizeNationalId((string) $this->id_number),
            ]);
        }
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
                'nullable',
                'required_if:id_type_id,'.$idType,
                'string',
                'max:20',
                new ZimbabweanIdNumber,
                Rule::unique('students', 'id_number')->ignore($studentId),
            ],

            'passport_number' => [
                'nullable',
                'required_if:id_type_id,'.$passportType,
                Rule::unique('students', 'passport_number')->ignore($studentId),
            ],
            'country_id' => ['required_if:id_type_id,'.$passportType, 'nullable', 'exists:countries,id'],
            'disability_status' => ['required', new Enum(DisabilityStatusEnum::class)],
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
            'input' => $this->all(),
        ], 422));
    }
}
