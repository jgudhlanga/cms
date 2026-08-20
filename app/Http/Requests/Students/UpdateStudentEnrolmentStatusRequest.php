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
                    StudentEnrolmentProgressionService::STATUS_ACTIVE,
                    StudentEnrolmentProgressionService::STATUS_ABSENT,
                    StudentEnrolmentProgressionService::STATUS_AWARD,
                    StudentEnrolmentProgressionService::STATUS_DEFERRED,
                    StudentEnrolmentProgressionService::STATUS_DISQUALIFIED,
                    StudentEnrolmentProgressionService::STATUS_PROCEED,
                    StudentEnrolmentProgressionService::STATUS_REFERRED,
                ]),
            ],
        ];
    }
}
