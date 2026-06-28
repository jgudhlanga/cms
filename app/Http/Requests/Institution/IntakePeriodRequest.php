<?php

namespace App\Http\Requests\Institution;

use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Models\Institution\IntakePeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        ];
    }
}
