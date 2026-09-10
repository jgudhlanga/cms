<?php

declare(strict_types=1);

namespace App\Http\Requests\Institution\Reconciliation;

use App\Models\Institution\InstitutionDepartment;
use Illuminate\Foundation\Http\FormRequest;

class EnrolmentVsClassListProcessRequest extends FormRequest
{
    /**
     * Authorize before validation so an unauthorized caller cannot use the difference between a
     * 422 and a 403 to probe which record ids exist.
     */
    public function authorize(): bool
    {
        $department = $this->route('department');

        return $department instanceof InstitutionDepartment
            && $this->user()?->can('updateDepartmentMetaData', $department) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $currentYear = (int) now()->format('Y');

        return [
            'calendar_year' => ['required', 'integer', 'min:'.($currentYear - 10), 'max:'.($currentYear + 10)],
            'rows' => ['required', 'array', 'min:1', 'max:1000'],
            'rows.*.rowNumber' => ['required', 'integer', 'min:1'],
            'rows.*.studentApplicationId' => ['required', 'integer', 'exists:student_applications,id'],
        ];
    }
}
