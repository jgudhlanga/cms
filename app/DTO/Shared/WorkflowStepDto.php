<?php

namespace App\DTO\Shared;

use App\Http\Requests\Shared\WorkflowStepRequest;

readonly class WorkflowStepDto
{
    public function __construct(
        public string  $name,
        public ?string $description,
    )
    {
    }


    public static function fromWorkflowStepRequest(WorkflowStepRequest $request): WorkflowStepDto
    {
        return new self(
            name: $request->name,
            description: $request->description,
        );
    }
}
