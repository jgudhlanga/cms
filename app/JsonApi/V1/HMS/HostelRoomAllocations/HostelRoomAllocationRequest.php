<?php

namespace App\JsonApi\V1\HMS\HostelRoomAllocations;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class HostelRoomAllocationRequest extends ResourceRequest
{
    public function rules(): array
    {
        return [
            'hostelRoomId' => ['required', 'integer', 'min:1'],
        ];
    }
}
