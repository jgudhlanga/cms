<?php

namespace App\Http\Requests\AcademicCalendars;

use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Staff;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignAcademicCalendarClassLecturerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('staff_id') && $this->input('staff_id') === '') {
            $this->merge(['staff_id' => null]);
        }
    }

    public function rules(): array
    {
        /** @var InstitutionDepartment|null $institutionDepartment */
        $institutionDepartment = $this->route('institution_department');

        return [
            'staff_id' => [
                'nullable',
                'integer',
                Rule::exists('staff', 'id')->whereNull('deleted_at'),
                Rule::when(
                    $this->filled('staff_id') && $institutionDepartment instanceof InstitutionDepartment,
                    Rule::exists('institution_department_staff', 'staff_id')
                        ->where('institution_department_id', $institutionDepartment->id),
                ),
            ],
        ];
    }
}
