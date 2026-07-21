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
            if (! $this->boolean('is_continuous')) {
                return;
            }

            $intakePeriod = $this->route('intake_period');
            $ignoreId = $intakePeriod instanceof IntakePeriod ? $intakePeriod->id : null;

            $existingActiveContinuous = IntakePeriod::query()
                ->where('is_continuous', true)
                ->where('is_active', true)
                ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists();

            if ($existingActiveContinuous) {
                $validator->errors()->add(
                    'is_continuous',
                    __('trans.intake_period_only_one_active_continuous'),
                );
            }
        });
    }
}
