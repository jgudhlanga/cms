<?php

namespace App\Http\Requests\Students;

use App\Enums\Shared\DisabilityStatusEnum;
use App\Enums\Shared\IdTypeEnum;
use App\Enums\Students\ApplicationTrackEnum;
use App\Helpers\PaymentHelper;
use App\Models\Institution\CourseRequirement;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelRequirement;
use App\Models\Institution\Level;
use App\Models\Institution\ModeOfStudy;
use App\Rules\Students\ValidateOLevelResults;
use App\Rules\ZimbabweanIdNumber;
use App\Services\Enrollment\EnrollmentLookupService;
use App\Services\Students\ApplicationEligibilityService;
use App\Services\Students\ApplicationFeeService;
use App\Services\Students\ApplicationTrackSession;
use App\Services\Students\RegistrationProgrammeAvailabilityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
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
            'date_of_birth' => ['required', 'date', 'before:today'],
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
            'employer' => ['nullable', 'string', 'max:255'],
            'apprentice_number' => ['nullable', 'string', 'max:255'],
            'required_level_completed' => ['nullable', 'boolean'],
            'read_write_acknowledged' => ['nullable', 'boolean'],
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

            $this->validateApplicationTrack($validator);
            $this->validateApplicationFee($validator);
            $this->validateApprenticeDetails($validator);
            $this->validateLevelAcknowledgements($validator);

            app(ValidateOLevelResults::class)->validate($this, $validator);
        });
    }

    protected function validateApplicationTrack(Validator $validator): void
    {
        $trackSession = app(ApplicationTrackSession::class);
        $eligibility = app(ApplicationEligibilityService::class);
        $track = $trackSession->require();

        $departmentLevel = DepartmentLevel::query()->with('level')->find($this->integer('level_id'));
        $mode = ModeOfStudy::query()->find($this->integer('mode_of_study_id'));

        if ($departmentLevel === null || $mode === null || $departmentLevel->level === null) {
            return;
        }

        $applicationFeeService = app(ApplicationFeeService::class);
        $intakePeriod = $track->usesContinuousIntake()
            ? $applicationFeeService->continuousIntakePeriod()
            : $applicationFeeService->resolveIntakeForSubmit(
                $this->user(),
                $this->filled('intake_period_id') ? $this->integer('intake_period_id') : ($trackSession->intakePeriodId())
            );

        if ($intakePeriod === null) {
            $validator->errors()->add('level_id', __('trans.application_track_not_open'));

            return;
        }

        try {
            $eligibility->assertTrackAllowsSubmit($track, $departmentLevel->level, $mode, $intakePeriod);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add($field, $message);
                }
            }
        }

        if ($track === ApplicationTrackEnum::Apprentice) {
            try {
                app(RegistrationProgrammeAvailabilityService::class)
                    ->assertProgrammeSelection(
                        $track,
                        (int) $departmentLevel->level_id,
                        (int) $this->integer('department_id'),
                        (int) $this->integer('level_id'),
                        (int) $this->integer('course_id'),
                        (int) $this->integer('mode_of_study_id'),
                    );
            } catch (ValidationException $e) {
                foreach ($e->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($field, $message);
                    }
                }
            }
        }
    }

    protected function validateApprenticeDetails(Validator $validator): void
    {
        $track = app(ApplicationTrackSession::class)->get();

        if ($track !== ApplicationTrackEnum::Apprentice) {
            return;
        }

        if (! $this->filled('employer')) {
            $validator->errors()->add('employer', __('validation.required', ['attribute' => 'employer']));
        }

        if (! $this->filled('apprentice_number')) {
            $validator->errors()->add(
                'apprentice_number',
                __('validation.required', ['attribute' => 'apprentice number']),
            );
        }
    }

    protected function validateLevelAcknowledgements(Validator $validator): void
    {
        $departmentLevelId = $this->integer('level_id');
        $departmentCourseId = $this->integer('course_id');

        $courseRequirement = CourseRequirement::query()
            ->where('department_level_id', $departmentLevelId)
            ->where('department_course_id', $departmentCourseId)
            ->first();

        $levelRequirement = DepartmentLevelRequirement::query()
            ->where('department_level_id', $departmentLevelId)
            ->first();

        $requiredLevelId = $courseRequirement?->required_level_id ?: $levelRequirement?->required_level_id;
        $onlyReadWrite = (bool) ($courseRequirement?->only_read_write_required || $levelRequirement?->only_read_write_required);

        if ($requiredLevelId && ! $this->boolean('required_level_completed')) {
            $validator->errors()->add(
                'required_level_completed',
                __('trans.acknowledge_level_completed'),
            );
        }

        if ($onlyReadWrite && ! $this->boolean('read_write_acknowledged')) {
            $validator->errors()->add(
                'read_write_acknowledged',
                __('trans.acknowledge_read_write'),
            );
        }
    }

    protected function validateApplicationFee(Validator $validator): void
    {
        $track = app(ApplicationTrackSession::class)->require();

        if ($track->skipsApplicationFee()) {
            return;
        }

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
            ?? ($track->usesContinuousIntake()
                ? $applicationFeeService->continuousIntakePeriod()
                : $applicationFeeService->resolveIntakeForSubmit(
                    $user,
                    $this->filled('intake_period_id') ? $this->integer('intake_period_id') : null
                ));

        if ($applicationFee === null || $intakePeriod === null || ! PaymentHelper::hasPaidApplicationFeeAndNotApplied($user, $intakePeriod)) {
            $validator->errors()->add(
                'level_id',
                __('trans.application_fee_payment_required'),
            );
        }
    }
}
