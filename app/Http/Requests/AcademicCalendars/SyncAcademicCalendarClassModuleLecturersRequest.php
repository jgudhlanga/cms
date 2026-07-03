<?php

namespace App\Http\Requests\AcademicCalendars;

use App\Models\Institution\InstitutionDepartment;
use App\Services\AcademicCalendars\ClassStaffingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SyncAcademicCalendarClassModuleLecturersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_option_id' => ['required', 'integer', 'exists:academic_year_options,id'],
            'course_syllabus_module_id' => ['required', 'integer', 'exists:course_syllabus_modules,id'],
            'staff_ids' => ['nullable', 'array'],
            'staff_ids.*' => ['integer', 'distinct', Rule::exists('staff', 'id')->whereNull('deleted_at')],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var InstitutionDepartment|null $institutionDepartment */
            $institutionDepartment = $this->route('institution_department');

            if (! $institutionDepartment instanceof InstitutionDepartment) {
                return;
            }

            $staffIds = array_map('intval', $this->input('staff_ids', []));

            if ($staffIds === []) {
                return;
            }

            $service = app(ClassStaffingService::class);

            if (! $service->assertAcademicStaffInDepartment($institutionDepartment, $staffIds)) {
                foreach ($staffIds as $index => $staffId) {
                    $validator->errors()->add(
                        "staff_ids.{$index}",
                        __('validation.exists', ['attribute' => 'staff_ids.'.$index]),
                    );
                }
            }
        });
    }
}
