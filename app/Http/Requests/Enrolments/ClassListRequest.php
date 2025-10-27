<?php

namespace App\Http\Requests\Enrolments;

use App\Enums\Shared\ClassListTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * @property mixed class_list
 */
class ClassListRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        if (is_string($this->class_list)) {
            $this->merge([
                'class_list' => json_decode($this->class_list, true),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'class_list' => ['required', 'array'],
            'type' => ['required', new Enum(ClassListTypeEnum::class)],
        ];
    }
}
