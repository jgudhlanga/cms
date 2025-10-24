<?php

namespace App\Http\Requests\Enrolments;

use App\Enums\Shared\ClassListTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * @property mixed class_lists
 */
class ClassListRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        if (is_string($this->class_lists)) {
            $this->merge([
                'class_lists' => json_decode($this->class_lists, true),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'class_lists' => ['required', 'array'],
            'type' => ['required', new Enum(ClassListTypeEnum::class)],
        ];
    }
}
