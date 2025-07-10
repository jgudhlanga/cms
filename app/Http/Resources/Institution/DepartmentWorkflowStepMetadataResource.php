<?php

namespace App\Http\Resources\Institution;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentWorkflowStepMetadataResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'department-workflow-step-metadata',
            'id' => $this->resource->id,
            'roleIds' => $this->role_ids,
            'staffIds' => $this->staff_ids,
            'workflowActionIds' => $this->workflow_action_ids,
            'roles' => $this->roles->pluck('name')->toArray(),
            'staff' => $this->staff->map(fn($s) => $s->user?->full_name)->toArray(),
            'actions' => $this->workflowAction->pluck('title')->toArray(),
        ];
    }
}
