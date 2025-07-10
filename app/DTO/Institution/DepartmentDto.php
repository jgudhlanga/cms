<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\DepartmentRequest;

readonly class DepartmentDto
{
    public function __construct(
        public string  $name,
        public bool  $is_academic,
        public ?string $description,
    )
    {
    }


    public static function fromDepartmentRequest(DepartmentRequest $request): DepartmentDto
    {
        return new self(
            name: $request->name,
            is_academic: $request->is_academic,
            description: $request->description,
        );
    }
}
