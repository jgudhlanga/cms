<?php

declare(strict_types=1);

namespace App\Http\Requests\Maintenance;

use App\Rules\ZimbabweanIdNumber;
use App\Services\Enrollment\EnrollmentLookupService;
use Illuminate\Foundation\Http\FormRequest;

class FixStudentIdNumberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
