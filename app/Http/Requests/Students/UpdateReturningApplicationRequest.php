<?php

namespace App\Http\Requests\Students;

use App\Enums\Shared\IdTypeEnum;
use App\Helpers\PaymentHelper;
use App\Models\Institution\Level;
use App\Rules\Students\ValidateOLevelResults;
use App\Rules\ZimbabweanIdNumber;
use App\Services\Students\ApplicationFeeService;
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
        $validator->after(function (Validator $validator) {
            $level = Level::query()->find($this->integer('level_id'));
            if ($level?->has_application_fee_payment) {
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

                if ($applicationFee !== null && (int) $applicationFee->level_id !== (int) $level->id) {
                    $validator->errors()->add(
                        'level_id',
                        __('trans.application_fee_level_mismatch'),
                    );
                }
            }

            app(ValidateOLevelResults::class)->validate($this, $validator);
        });
    }
}
