<?php

namespace App\JsonApi\V1\HMS\HmsSettings;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class HmsSettingRequest extends ResourceRequest
{
    public function rules(): array
    {
        $applicationsOpen = (bool) $this->input('data.attributes.applicationsOpen');

        return [
            'requireFullTimeStudy' => ['sometimes', 'boolean'],
            'fullTimeModeName' => ['sometimes', 'string', 'max:255'],
            'requireTuitionPaid' => ['sometimes', 'boolean'],
            'requireAccommodationPaid' => ['sometimes', 'boolean'],
            'requireAddressOutsideCampus' => ['sometimes', 'boolean'],
            'campusCity' => ['sometimes', 'string', 'max:255'],
            'allowGuests' => ['sometimes', 'boolean'],
            'autoAllocateRooms' => ['sometimes', 'boolean'],
            'daysToPay' => ['sometimes', 'integer', 'min:1', 'max:365'],
            'applicationsOpen' => ['sometimes', 'boolean'],
            'applicationStartDate' => [
                Rule::requiredIf($applicationsOpen),
                'nullable',
                'date',
            ],
            'applicationEndDate' => [
                Rule::requiredIf($applicationsOpen),
                'nullable',
                'date',
                'after_or_equal:applicationStartDate',
            ],
        ];
    }
}
