<?php

namespace App\Http\Requests\Enrolments;

use App\Enums\Shared\ClassListTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class BulkAddToClassListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create:class-lists') ?? false;
    }

    public function rules(): array
    {
        return [
            'application_ids' => ['required', 'array', 'min:1'],
            'application_ids.*' => ['required', 'integer', 'exists:student_applications,id'],
            'type' => ['required', new Enum(ClassListTypeEnum::class)],
        ];
    }
}
