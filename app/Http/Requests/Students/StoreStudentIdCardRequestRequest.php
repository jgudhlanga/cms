<?php

declare(strict_types=1);

namespace App\Http\Requests\Students;

use App\Enums\Students\IdCardRequestReasonEnum;
use App\Models\Students\StudentIdCardRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentIdCardRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', StudentIdCardRequest::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', Rule::enum(IdCardRequestReasonEnum::class)],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
