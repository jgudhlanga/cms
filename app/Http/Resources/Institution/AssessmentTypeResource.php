<?php

namespace App\Http\Resources\Institution;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssessmentTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'assessment-type',
            'id' => $this->resource->id,
            'attributes' => [
                'name' => $this->resource->name,
                'modesOfStudy' => $this->resource->modeOfStudyNames(),
                'modesOfStudyIds' => $this->resource->modes_of_study,
                'description' => $this->resource->description,
                $this->mergeWhen($request->routeIs('assessment-types.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ]),
            ],
        ];
    }
}
