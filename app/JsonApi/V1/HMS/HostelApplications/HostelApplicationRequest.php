<?php

namespace App\JsonApi\V1\HMS\HostelApplications;

use App\Enums\HMS\HostelApplicationStatusEnum;
use App\Enums\HMS\HostelApplicationTypeEnum;
use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class HostelApplicationRequest extends ResourceRequest
{
    public function rules(): array
    {
        $isCreating = $this->isCreating();

        return [
            'applicationType' => [
                $isCreating ? 'required' : 'sometimes',
                Rule::enum(HostelApplicationTypeEnum::class),
            ],
            'status' => [
                'sometimes',
                Rule::enum(HostelApplicationStatusEnum::class),
            ],
            'studentId' => [
                Rule::requiredIf(fn () => $this->input('applicationType') === HostelApplicationTypeEnum::STUDENT->value),
                'nullable',
                'integer',
                'exists:students,id',
            ],
            'studentEnrolmentId' => ['nullable', 'integer', 'exists:student_enrolments,id'],
            'name' => [
                Rule::requiredIf(fn () => $this->input('applicationType') === HostelApplicationTypeEnum::GUEST->value),
                'nullable',
                'string',
                'max:255',
            ],
            'genderId' => [
                Rule::requiredIf(fn () => $this->input('applicationType') === HostelApplicationTypeEnum::GUEST->value),
                'nullable',
                'integer',
                'exists:genders,id',
            ],
            'phoneNumber' => ['nullable', 'string', 'max:50'],
            'emailAddress' => ['nullable', 'email', 'max:255'],
            'nextOfKinName' => [$isCreating ? 'required' : 'sometimes', 'string', 'max:255'],
            'nextOfKinContact' => [$isCreating ? 'required' : 'sometimes', 'string', 'max:50'],
            'checkIn' => [$isCreating ? 'required' : 'sometimes', 'date'],
            'checkOut' => [$isCreating ? 'required' : 'sometimes', 'date', 'after:checkIn'],
            'declineReason' => [
                Rule::requiredIf(fn () => $this->input('status') === HostelApplicationStatusEnum::DECLINED->value),
                'nullable',
                'string',
                'max:1000',
            ],
            'hostelRoomId' => [
                Rule::requiredIf(fn () => $this->input('status') === HostelApplicationStatusEnum::APPROVED->value),
                'nullable',
                'integer',
                'exists:hostel_rooms,id',
            ],
            'paymentVerification' => ['sometimes', 'nullable', 'array'],
            'paymentVerification.addressOutsideCityCampusConfirmed' => ['sometimes', 'boolean'],
            'paymentVerification.fullTimeStudentConfirmed' => ['sometimes', 'boolean'],
            'paymentVerification.tuitionFeesPaidConfirmed' => ['sometimes', 'boolean'],
            'paymentVerification.accommodationFeesPaidConfirmed' => ['sometimes', 'boolean'],
        ];
    }
}
