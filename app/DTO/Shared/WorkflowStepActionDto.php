<?php

namespace App\DTO\Shared;

use App\Http\Requests\Shared\WorkflowStepActionRequest;

readonly class WorkflowStepActionDto
{
    public function __construct(
        public string   $title,
    )
    {
    }


    public static function fromWorkflowStepActionRequest(WorkflowStepActionRequest $request): WorkflowStepActionDto
    {
        return new self(
            title: $request->title,
        );
    }
}
