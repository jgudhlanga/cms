<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\DepartmentRequest;

readonly class DepartmentDto
{
    public function __construct(
        public string  $name,
        public ?string $description,
    )
    {
    }


    public static function fromDepartmentRequest(DepartmentRequest $request): DepartmentDto
    {
        return new self(
            name: $request->name,
            description: $request->description,
        );
    }
}
