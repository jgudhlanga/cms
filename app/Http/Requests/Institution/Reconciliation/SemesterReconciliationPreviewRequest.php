<?php

declare(strict_types=1);

namespace App\Http\Requests\Institution\Reconciliation;

use App\Models\Institution\InstitutionDepartment;
use App\Rules\Institution\AcceptedDepartmentReconciliationImportFile;
use Illuminate\Foundation\Http\FormRequest;

class SemesterReconciliationPreviewRequest extends FormRequest
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
            'file' => ['required', 'file', 'max:10240', new AcceptedDepartmentReconciliationImportFile],
            'calendar_year' => ['required', 'integer', 'min:'.($currentYear - 10), 'max:'.($currentYear + 10)],
            'mode_of_study_id' => ['nullable', 'integer', 'exists:mode_of_studies,id'],
            'department_level_id' => ['nullable', 'integer', 'exists:department_levels,id'],
            'department_course_id' => ['nullable', 'integer', 'exists:department_courses,id'],
        ];
    }
}
