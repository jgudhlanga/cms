<?php

namespace App\Http\Requests\Students;

use App\Enums\Shared\IdTypeEnum;
use App\Models\Institution\DepartmentLevel;
use App\Rules\ZimbabweanIdNumber;
use App\Services\Students\ReturningStudentContextService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateReturningApplicationRequest extends CreateApplicationRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $studentId = $this->user()?->studentProfile?->id;
        $idType = IdTypeEnum::ZIMBABWEAN_ID_NUMBER->id();
        $passportType = IdTypeEnum::FOREIGN_PASSPORT_NUMBER->id();

        $rules['id_number'] = [
            'required_if:id_type_id,'.$idType,
            'nullable',
            'string',
            'max:20',
            new ZimbabweanIdNumber,
            Rule::unique('students', 'id_number')->ignore($studentId),
        ];

        $rules['passport_number'] = [
            'required_if:id_type_id,'.$passportType,
            'nullable',
            'string',
            'min:5',
            'max:50',
            Rule::unique('students', 'passport_number')->ignore($studentId),
        ];

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);

        $validator->after(function (Validator $validator): void {
            $this->validateNextLevelProgramme($validator);
        });
    }

    protected function validateNextLevelProgramme(Validator $validator): void
    {
        $student = $this->user()?->studentProfile;
        if ($student === null) {
            return;
        }

        $context = app(ReturningStudentContextService::class)->nextLevelApplicationContext($student);
        if (($context['canApplyToNextLevel'] ?? false) !== true) {
            return;
        }

        $expectedDepartmentLevelId = $context['nextDepartmentLevelId'] ?? null;
        $expectedDepartmentId = $context['institutionDepartmentId'] ?? null;
        $nextLevelName = $context['nextLevelName'] ?? '';

        if (! is_int($expectedDepartmentLevelId) || $expectedDepartmentLevelId < 1) {
            return;
        }

        $submittedDepartmentLevelId = $this->integer('level_id');
        if ($submittedDepartmentLevelId !== $expectedDepartmentLevelId) {
            $validator->errors()->add(
                'level_id',
                __('trans.returning_student_next_level_only', ['level' => $nextLevelName]),
            );
        }

        if (
            is_int($expectedDepartmentId)
            && $expectedDepartmentId > 0
            && $this->integer('department_id') !== $expectedDepartmentId
        ) {
            $validator->errors()->add(
                'department_id',
                __('trans.returning_student_next_level_department_mismatch'),
            );
        }

        $departmentLevel = DepartmentLevel::query()->find($submittedDepartmentLevelId);
        if (
            $departmentLevel instanceof DepartmentLevel
            && is_int($expectedDepartmentId)
            && (int) $departmentLevel->institution_department_id !== $expectedDepartmentId
        ) {
            $validator->errors()->add(
                'level_id',
                __('trans.returning_student_next_level_department_mismatch'),
            );
        }
    }
}
