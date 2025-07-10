<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\WorkflowStepActionMetadataRequest;

readonly class WorkflowStepActionMetadataDto
{
    public function __construct(
        public int    $department_application_step_id,
        public ?array $role_ids,
        public ?array $staff_ids,
        public ?array $workflow_action_ids,
    )
    {
    }


    public static function fromWorkflowStepActionMetadataRequest(WorkflowStepActionMetadataRequest $request): WorkflowStepActionMetadataDto
    {
        return new self(
            department_application_step_id: $request->department_application_step_id,
            role_ids: $request->role_ids,
            staff_ids: $request->staff_ids,
            workflow_action_ids: $request->workflow_action_ids,
        );
    }
}
