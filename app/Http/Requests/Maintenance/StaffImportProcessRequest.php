<?php

declare(strict_types=1);

namespace App\Http\Requests\Maintenance;

use Illuminate\Foundation\Http\FormRequest;

class StaffImportProcessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'preview_token' => ['required', 'string'],
            'file' => ['prohibited'],
            'row_corrections' => ['sometimes', 'array'],
            'row_corrections.*' => ['array'],
            'row_corrections.*.titleId' => ['sometimes', 'integer', 'exists:titles,id'],
            'row_corrections.*.genderId' => ['sometimes', 'integer', 'exists:genders,id'],
            'row_corrections.*.maritalStatusId' => ['sometimes', 'integer', 'exists:marital_statuses,id'],
            'row_corrections.*.employmentTypeId' => ['sometimes', 'integer', 'exists:employment_types,id'],
            'row_corrections.*.institutionDepartmentId' => ['sometimes', 'integer', 'exists:institution_departments,id'],
            'row_corrections.*.roleIds' => ['sometimes', 'array'],
            'row_corrections.*.roleIds.*' => ['integer', 'exists:roles,id'],
            'row_corrections.*.email' => ['sometimes', 'string', 'email', 'max:255'],
            'row_corrections.*.phoneNumber' => ['sometimes', 'string', 'max:30'],
            'row_corrections.*.dateOfBirth' => ['sometimes', 'date'],
            'excluded_row_numbers' => ['sometimes', 'array'],
            'excluded_row_numbers.*' => ['integer', 'min:1'],
        ];
    }
}
