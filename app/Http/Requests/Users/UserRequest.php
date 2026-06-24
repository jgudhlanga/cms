<?php

namespace App\Http\Requests\Users;

use App\Rules\ZimbabweanIdNumber;
use App\Services\Enrollment\EnrollmentLookupService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
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

        if ($this->filled('id_number')) {
            $this->merge([
                'id_number' => EnrollmentLookupService::normalizeNationalId((string) $this->id_number),
            ]);
        }

        if ($this->filled('passport_number')) {
            $this->merge([
                'passport_number' => strtoupper(trim((string) $this->passport_number)),
            ]);
        }
    }

    public function rules(): array
    {
        $path = $this->string('registration_path')->toString();

        return [
            'registration_path' => ['required', 'string', Rule::in(['zimbabwean', 'international'])],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'id_number' => [
                Rule::requiredIf($path === 'zimbabwean'),
                'nullable',
                'string',
                'max:20',
                new ZimbabweanIdNumber,
                Rule::unique('students', 'id_number'),
            ],
            'passport_number' => [
                Rule::requiredIf($path === 'international'),
                'nullable',
                'string',
                'min:5',
                'max:50',
                Rule::unique('students', 'passport_number'),
            ],
            'acknowledged_advert' => ['required', 'accepted'],
        ];
    }
}
