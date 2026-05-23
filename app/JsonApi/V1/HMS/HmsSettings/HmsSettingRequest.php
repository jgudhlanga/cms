<?php

namespace App\JsonApi\V1\HMS\HmsSettings;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class HmsSettingRequest extends ResourceRequest
{
    public function rules(): array
    {
        return [
            'requireFullTimeStudy' => ['sometimes', 'boolean'],
            'fullTimeModeName' => ['sometimes', 'string', 'max:255'],
            'requireTuitionPaid' => ['sometimes', 'boolean'],
            'requireAddressOutsideCampus' => ['sometimes', 'boolean'],
            'campusCity' => ['sometimes', 'string', 'max:255'],
            'allowGuests' => ['sometimes', 'boolean'],
        ];
    }
}
