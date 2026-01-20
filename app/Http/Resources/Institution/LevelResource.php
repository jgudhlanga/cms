<?php

namespace App\Http\Resources\Institution;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LevelResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'level',
            'id' => $this->resource->id,
            "attributes" => [
                'name' => $this->resource->name,
                'position' => $this->resource->position,
                'description' => $this->resource->description,
                'allowedApplicationsPerLevel' => $this->resource->allowed_applications_per_level,
                'showOnCurrentApplicationPeriod' => $this->resource->show_on_current_application_period,
                'hasApplicationFeePayment' => $this->resource->has_application_fee_payment,
                $this->mergeWhen($request->routeIs('levels.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ]),
            ]
        ];
    }
}
