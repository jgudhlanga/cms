<?php

declare(strict_types=1);

namespace App\Http\Requests\Students;

use App\Services\Students\StudentEnrolmentProgressionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentEnrolmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([
                    StudentEnrolmentProgressionService::STATUS_REPEAT,
                    StudentEnrolmentProgressionService::STATUS_DEFERRED,
                    StudentEnrolmentProgressionService::STATUS_COMPLETED,
                    StudentEnrolmentProgressionService::STATUS_ACTIVE,
                    'repeat-re-write',
                    'deferred-postponed',
                ]),
            ],
        ];
    }
}
