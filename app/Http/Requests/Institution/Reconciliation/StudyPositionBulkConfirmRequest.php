<?php

declare(strict_types=1);

namespace App\Http\Requests\Institution\Reconciliation;

use App\Models\Institution\InstitutionDepartment;
use Illuminate\Foundation\Http\FormRequest;

class StudyPositionBulkConfirmRequest extends FormRequest
{
    /**
     * Authorized here only at the department level; each enrolment is re-checked against
     * StudentPolicy::confirmStudyPosition before it is written, the same as the single-student flow.
     */
    public function authorize(): bool
    {
        $department = $this->route('department');

        return $department instanceof InstitutionDepartment
            && $this->user()?->can('confirm-study-position:students') === true
            && $this->user()?->can('updateDepartmentMetaData', $department) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'enrolment_ids' => ['required', 'array', 'min:1', 'max:500'],
            'enrolment_ids.*' => ['integer', 'distinct'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }
}
