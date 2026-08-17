<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Students\StudentIdCardRequests;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class StudentIdCardRequestRequest extends ResourceRequest
{
    public function rules(): array
    {
        return [];
    }
}
