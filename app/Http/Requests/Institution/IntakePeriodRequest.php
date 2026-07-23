<?php

namespace App\Http\Requests\Institution;

use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Models\Institution\IntakePeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class IntakePeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $intakePeriod = $this->route('intake_period');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('intake_periods', 'name')->ignore($intakePeriod instanceof IntakePeriod ? $intakePeriod->id : $intakePeriod),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(IntakePeriodStatusEnum::class)],
            'is_continuous' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('status') !== IntakePeriodStatusEnum::Open->value) {
                return;
            }

            $intakePeriod = $this->route('intake_period');
            $ignoreId = $intakePeriod instanceof IntakePeriod ? $intakePeriod->id : null;
            $isContinuous = $this->boolean('is_continuous');

            if ($isContinuous) {
                if ($this->openIntakeExists(isContinuous: true, ignoreId: $ignoreId)) {
                    $validator->errors()->add(
                        'is_continuous',
                        __('trans.intake_period_only_one_open_continuous'),
                    );
                }

                if ($this->openIntakeExists(isContinuous: false, ignoreId: $ignoreId)) {
                    $validator->errors()->add(
                        'status',
                        __('trans.intake_period_continuous_blocked_by_open_regular'),
                    );
                }

                return;
            }

            if ($this->openIntakeExists(isContinuous: false, ignoreId: $ignoreId)) {
                $validator->errors()->add(
                    'status',
                    __('trans.intake_period_only_one_open_regular'),
                );
            }
        });
    }

    private function openIntakeExists(bool $isContinuous, int|string|null $ignoreId): bool
    {
        return IntakePeriod::query()
            ->where('is_continuous', $isContinuous)
            ->where('is_active', true)
            ->where('status', IntakePeriodStatusEnum::Open)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }
}
