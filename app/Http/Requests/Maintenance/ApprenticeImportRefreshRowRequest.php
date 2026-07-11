<?php

declare(strict_types=1);

namespace App\Http\Requests\Maintenance;

use Illuminate\Foundation\Http\FormRequest;

class ApprenticeImportRefreshRowRequest extends FormRequest
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
        $currentYear = (int) now()->format('Y');

        return [
            'institution_department_id' => ['required', 'integer', 'exists:institution_departments,id'],
            'calendar_year' => ['required', 'integer', 'min:'.($currentYear - 10), 'max:'.($currentYear + 10)],
            'rowNumber' => ['required', 'integer', 'min:1'],
            'idNumber' => ['nullable', 'string', 'max:255'],
            'studentNumber' => ['nullable', 'string', 'max:255'],
            'apprenticeNumber' => ['nullable', 'string', 'max:255'],
            'employer' => ['nullable', 'string', 'max:255'],
        ];
    }
}
