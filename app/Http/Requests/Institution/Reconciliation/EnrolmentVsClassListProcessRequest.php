<?php

declare(strict_types=1);

namespace App\Http\Requests\Institution\Reconciliation;

use Illuminate\Foundation\Http\FormRequest;

class EnrolmentVsClassListProcessRequest extends FormRequest
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
            'calendar_year' => ['required', 'integer', 'min:'.($currentYear - 10), 'max:'.($currentYear + 10)],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.rowNumber' => ['required', 'integer', 'min:1'],
            'rows.*.studentApplicationId' => ['required', 'integer', 'exists:student_applications,id'],
        ];
    }
}
