<?php

namespace App\Http\Resources\Shared;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LanguageResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'language',
            'id' => $this->resource->id,
            "attributes" => [
                'title' => $this->resource->title,
                $this->mergeWhen($request->routeIs('languages.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
					'deletedAt' => $this->resource->deleted_at,
                ]),
            ]
        ];
    }
}
