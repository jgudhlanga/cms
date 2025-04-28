<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\InstitutionDepartmentRequest;

readonly class InstitutionDepartmentDto
{
    public function __construct(
        public string  $department_id,
        public ?string $description,
    )
    {
    }


    public static function fromInstitutionDepartmentRequest(InstitutionDepartmentRequest $request): InstitutionDepartmentDto
    {
        return new self(
            department_id: $request->department_id,
            description: $request->description,
        );
    }
}
