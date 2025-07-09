<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\DepartmentApplicationStepUpdateRequest;

readonly class DepartmentApplicationStepUpdateDto
{
    public function __construct(
        public array $role_ids,
        public array $staff_ids,
    )
    {
    }

    public static function fromDepartmentApplicationStepUpdateRequest(DepartmentApplicationStepUpdateRequest $request): DepartmentApplicationStepUpdateDto
    {
        return new self(
            role_ids: $request->role_ids,
            staff_ids: $request->staff_ids,
        );
    }
}
