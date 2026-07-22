<?php

declare(strict_types=1);

namespace App\Http\Requests\Students;

use App\Models\Students\Student;
use App\Rules\ZimbabweanIdNumber;
use App\Services\Enrollment\EnrollmentLookupService;
use Illuminate\Foundation\Http\FormRequest;

class FixStudentIdNumberRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Student $student */
        $student = $this->route('student');
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        if ($user->can('update:students')) {
            return true;
        }

        return $user->studentProfile?->id === $student->id
            && $user->can('manageOwnStudentPersonalDetails:students');
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('id_number')) {
            $this->merge([
                'id_number' => EnrollmentLookupService::normalizeNationalId((string) $this->id_number),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'id_number' => [
                'required',
                'string',
                'max:20',
                new ZimbabweanIdNumber,
            ],
        ];
    }
}
