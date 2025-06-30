<?php

namespace App\DTO\Shared;

use App\Http\Requests\Shared\ApplicationStepRequest;

readonly class ApplicationStepDto
{
    public function __construct(
        public string  $name,
        public ?string $description,
    )
    {
    }


    public static function fromApplicationStepRequest(ApplicationStepRequest $request): ApplicationStepDto
    {
        return new self(
            name: $request->name,
            description: $request->description,
        );
    }
}
