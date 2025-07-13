<?php

namespace App\Http\Resources\Institution;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstitutionDepartmentWithWorkflowStepsResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'department' => new InstitutionDepartmentResource($this),
            'steps' => DepartmentApplicationStepResource::collection(
                $this->applicationSteps()->orderBy('position')->get()
            ),
        ];
    }
}
