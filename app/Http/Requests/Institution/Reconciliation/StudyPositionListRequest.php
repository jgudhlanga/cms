<?php

declare(strict_types=1);

namespace App\Http\Requests\Institution\Reconciliation;

use App\Enums\Students\StudyPositionStateEnum;
use App\Models\Institution\InstitutionDepartment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudyPositionListRequest extends FormRequest
{
    public function authorize(): bool
    {
        $department = $this->route('department');

        return $department instanceof InstitutionDepartment
            && $this->user()?->can('confirm-study-position:students') === true
            && $this->user()?->can('viewDepartmentMetaData', $department) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'state' => ['required', Rule::enum(StudyPositionStateEnum::class)],
            'mode_of_study_id' => ['nullable', 'integer'],
            'department_level_id' => ['nullable', 'integer'],
            'department_course_id' => ['nullable', 'integer'],
        ];
    }
}
