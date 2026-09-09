<?php

declare(strict_types=1);

namespace App\Http\Requests\Institution\Reconciliation;

use Illuminate\Foundation\Http\FormRequest;

class SemesterReconciliationProcessRequest extends FormRequest
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
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.rowNumber' => ['required', 'integer', 'min:1'],
            'rows.*.studentEnrolmentId' => ['required', 'integer', 'exists:student_enrolments,id'],
            'rows.*.programmeSemesterId' => ['required', 'integer', 'exists:programme_semesters,id'],
        ];
    }
}
