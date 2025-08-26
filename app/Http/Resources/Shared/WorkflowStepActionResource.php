<?php

namespace App\Http\Resources\Shared;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowStepActionResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'workflow-step-action',
            'id' => $this->resource->id,
            "attributes" => [
                'name' => $this->resource->name,
                'slug' => $this->resource->slug,
                'title' => $this->resource->title,
                $this->mergeWhen($request->routeIs('workflow-step-actions.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ]),
            ]
        ];
    }
}
