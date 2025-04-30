<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\InstitutionDepartmentRequest;

readonly class InstitutionDepartmentDto
{
    public function __construct(
        public array  $department_ids,
    )
    {
    }


    public static function fromInstitutionDepartmentRequest(InstitutionDepartmentRequest $request): InstitutionDepartmentDto
    {
        return new self(
            department_ids: $request->department_ids,
        );
    }
}
