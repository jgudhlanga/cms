<?php

declare(strict_types=1);

namespace App\Http\Requests\Maintenance;

use App\Rules\ZimbabweanIdNumber;
use App\Services\Enrollment\EnrollmentLookupService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MergeStudentAccountsRequest extends FormRequest
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
            'source_student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'target_student_id' => ['required', 'integer', Rule::exists('students', 'id'), 'different:source_student_id'],
            'survivor_student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id'),
                Rule::in([
                    (int) $this->input('source_student_id'),
                    (int) $this->input('target_student_id'),
                ]),
            ],
            'id_number' => ['required', 'string', 'max:20', new ZimbabweanIdNumber],
        ];
    }
}
