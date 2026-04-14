<?php

namespace App\Http\Resources\Students;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentEnrolmentStatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'student-enrolment-status',
            'id' => $this->resource->id,
            'attributes' => [
                'name' => $this->resource->name,
                'description' => $this->resource->description,
                'color' => $this->resource->color,
                $this->mergeWhen($request->routeIs('student-enrolment-statuses.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ]),
            ],
        ];
    }
}
