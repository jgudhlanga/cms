<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\DepartmentApplicationStepRequest;

readonly class DepartmentApplicationStepDto
{
    public function __construct(
        public array $workflow_step_ids,
    )
    {
    }


    public static function fromDepartmentApplicationStepRequest(DepartmentApplicationStepRequest $request): DepartmentApplicationStepDto
    {
        return new self(
            workflow_step_ids: $request->workflow_step_ids,
        );
    }
}
