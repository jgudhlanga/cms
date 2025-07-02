<?php

namespace App\DTO\Shared;

use App\Http\Requests\Shared\EmploymentTypeRequest;

readonly class EmploymentTypeDto
{
    public function __construct(
        public string   $name,
		public ? string $description,
    )
    {
    }


    public static function fromEmploymentTypeRequest(EmploymentTypeRequest $request): EmploymentTypeDto
    {
        return new self(
            name: $request->name,
			description: $request->description,
        );
    }
}
