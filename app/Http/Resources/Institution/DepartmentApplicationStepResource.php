<?php

namespace App\Http\Resources\Institution;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentApplicationStepResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'department-application-step',
            'id' => $this->resource->id,
            "attributes" => [
                "institutionDepartmentId" => $this->institution_department_id,
                "workflowStepId" => $this->workflow_step_id,
                "workflowStep" => $this->workflowStep?->name,
                "slug" => $this->workflowStep?->slug,
                "workflowStepDescription" => $this->workflowStep?->description,
                'position' => $this->position,
                'createdAt' => $this->resource->created_at,
                'updatedAt' => $this->resource->updated_at,
                'deletedAt' => $this->resource->deleted_at,
            ],
            "relationships" => [
                'metadata' => DepartmentWorkflowStepMetadataResource::make($this?->workflowStep?->metadata?->first()),
            ],
        ];
    }
}
