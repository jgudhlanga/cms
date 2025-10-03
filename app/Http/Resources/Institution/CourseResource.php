<?php

namespace App\Http\Resources\Institution;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'course',
            'id' => $this->resource->id,
            "attributes" => [
                'name' => $this->resource->name,
                'slug' => $this->resource->slug,
                'position' => $this->resource->position,
                'description' => $this->resource->description,
                'hasEnrolmentRequirements' => $this->resource->has_enrolment_requirements,
                $this->mergeWhen($request->routeIs('courses.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ]),
            ]
        ];
    }
}
