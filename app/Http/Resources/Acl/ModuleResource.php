<?php

namespace App\Http\Resources\Acl;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'module',
            'id' => $this->resource->id,
            'attributes' => [
                'title' => $this->resource->title,
                'slug' => $this->resource->slug,
                'description' => $this->resource->description,
                'status' => $this->resource->status,
                'settings' => $this->resource->settings,
                $this->mergeWhen($request->routeIs('modules.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ]),
            ],
        ];
    }
}
