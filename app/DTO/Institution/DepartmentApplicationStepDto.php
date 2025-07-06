<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\DepartmentApplicationStepRequest;

readonly class DepartmentApplicationStepDto
{
    public function __construct(
        public array $application_step_ids,
    )
    {
    }


    public static function fromDepartmentApplicationStepRequest(DepartmentApplicationStepRequest $request): DepartmentApplicationStepDto
    {
        return new self(
            application_step_ids: $request->application_step_ids,
        );
    }
}
