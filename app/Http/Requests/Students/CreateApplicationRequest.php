<?php

namespace App\Http\Requests\Students;

use App\Enums\Shared\DisabilityStatusEnum;
use App\Enums\Shared\IdTypeEnum;
use App\Helpers\PaymentHelper;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\Level;
use App\Rules\Students\ValidateOLevelResults;
use App\Rules\ZimbabweanIdNumber;
use App\Services\Enrollment\EnrollmentLookupService;
use App\Services\Students\ApplicationFeeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

/**
 * @property mixed $o_level_subject_ids
 * @property mixed $o_level_years
 * @property mixed $o_level_sittings
 * @property mixed $o_level_other_subject_ids
 * @property mixed $o_level_other_grade_ids
 * @property mixed $o_level_other_years
 * @property mixed $o_level_other_sittings
 */
class CreateApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        if (is_string($this->o_level_subject_ids)) {
            $this->merge([
                'o_level_subject_ids' => json_decode($this->o_level_subject_ids, true),
            ]);
        }
        if (is_string($this->o_level_years)) {
            $this->merge([
                'o_level_years' => json_decode($this->o_level_years, true),
            ]);
        }
        if (is_string($this->o_level_sittings)) {
            $this->merge([
                'o_level_sittings' => json_decode($this->o_level_sittings, true),
            ]);
        }
        if (is_string($this->o_level_other_subject_ids)) {
            $this->merge([
                'o_level_other_subject_ids' => json_decode($this->o_level_other_subject_ids, true),
            ]);
        }
        if (is_string($this->o_level_other_grade_ids)) {
            $this->merge([
                'o_level_other_grade_ids' => json_decode($this->o_level_other_grade_ids, true),
            ]);
        }
        if (is_string($this->o_level_other_years)) {
            $this->merge([
                'o_level_other_years' => json_decode($this->o_level_other_years, true),
            ]);
        }
        if (is_string($this->o_level_other_sittings)) {
            $this->merge([
                'o_level_other_sittings' => json_decode($this->o_level_other_sittings, true),
            ]);
        }

        if ($this->filled('id_number')) {
            $this->merge([
                'id_number' => EnrollmentLookupService::normalizeNationalId((string) $this->id_number),
            ]);
        }

        if ($this->filled('passport_number')) {
            $this->merge([
                'passport_number' => EnrollmentLookupService::normalizePassportNumber((string) $this->passport_number),
            ]);
        }
    }

    public function rules(): array
    {
        $idType = IdTypeEnum::ZIMBABWEAN_ID_NUMBER->id();
        $passportType = IdTypeEnum::FOREIGN_PASSPORT_NUMBER->id();

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'gender_id' => ['required', 'integer', 'exists:genders,id'],
            'marital_status_id' => ['required', 'integer', 'exists:marital_statuses,id'],
            'title_id' => ['required', 'integer', 'exists:titles,id'],
            'mode_of_study_id' => ['required', 'integer', 'exists:mode_of_studies,id'],
            'id_type_id' => ['required', 'integer', 'exists:id_types,id'],
            'id_number' => [
                'required_if:id_type_id,'.$idType,
                'nullable',
                'string',
                'max:20',
                new ZimbabweanIdNumber,
                Rule::unique('students', 'id_number'),
            ],
            'passport_number' => [
                'required_if:id_type_id,'.$passportType,
                'nullable',
                'string',
                'min:5',
                'max:50',
                Rule::unique('students', 'passport_number'),
            ],
            'country_id' => ['required_if:id_type_id,'.$passportType, 'nullable', 'exists:countries,id'],
            'address_1' => ['required', 'string', 'max:255'],
            'address_2' => ['required', 'string', 'max:255'],
            'address_3' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255', 'email'],
            'phone_number' => ['required', 'string', 'max:30'],
            'next_of_kin_name' => ['required', 'string', 'max:255'],
            'next_of_kin_address_1' => ['required', 'string', 'max:255'],
            'next_of_kin_address_2' => ['required', 'string', 'max:255'],
            'next_of_kin_address_3' => ['required', 'string', 'max:255'],
            'relationship_id' => ['required', 'integer', 'exists:relationships,id'],
            'next_of_kin_phone_number' => ['required', 'string', 'max:30'],
            'department_id' => ['required', 'integer'],
            'level_id' => ['required', 'integer'],
            'course_id' => ['required', 'integer'],
            'disability_status' => ['required', new Enum(DisabilityStatusEnum::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $sessionIdNumber = session('registration.id_number');
            if ($sessionIdNumber && $this->filled('id_number')) {
                $normalized = EnrollmentLookupService::normalizeNationalId((string) $this->id_number);
                if ($normalized !== $sessionIdNumber) {
                    $validator->errors()->add(
                        'id_number',
                        __('trans.registration_id_mismatch'),
                    );
                }
            }

            $sessionPassport = session('registration.passport_number');
            if ($sessionPassport && $this->filled('passport_number')) {
                $normalized = EnrollmentLookupService::normalizePassportNumber((string) $this->passport_number);
                if ($normalized !== $sessionPassport) {
                    $validator->errors()->add(
                        'passport_number',
                        __('trans.registration_passport_mismatch'),
                    );
                }
            }

            $this->validateApplicationFee($validator);

            app(ValidateOLevelResults::class)->validate($this, $validator);
        });
    }

    protected function validateApplicationFee(Validator $validator): void
    {
        $departmentLevel = DepartmentLevel::query()->find($this->integer('level_id'));

        if ($departmentLevel === null) {
            return;
        }

        if ((int) $departmentLevel->institution_department_id !== (int) $this->integer('department_id')) {
            $validator->errors()->add(
                'level_id',
                __('validation.exists', ['attribute' => 'level']),
            );
        }

        if (! $departmentLevel->show_on_current_application_period) {
            $validator->errors()->add(
                'level_id',
                __('trans.portal_selected_level_not_active_toast'),
            );
        }

        $institutionLevel = Level::query()->find($departmentLevel->level_id);

        if (
            $institutionLevel === null
            || ! PaymentHelper::levelRequiresApplicationFeePayment($institutionLevel, $this->user())
        ) {
            return;
        }

        $user = $this->user();
        $applicationFeeService = app(ApplicationFeeService::class);
        $applicationFee = $applicationFeeService->activeApplicationFee($user);
        $intakePeriod = $applicationFee?->intakePeriod
            ?? $applicationFeeService->resolveIntakeForSubmit(
                $user,
                $this->filled('intake_period_id') ? $this->integer('intake_period_id') : null
            );

        if ($applicationFee === null || ! PaymentHelper::hasPaidApplicationFeeAndNotApplied($user, $intakePeriod)) {
            $validator->errors()->add(
                'level_id',
                __('trans.application_fee_payment_required'),
            );
        }
    }
}
