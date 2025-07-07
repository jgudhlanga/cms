<?php

namespace App\Http\Resources\Acl;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleGroupResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            "type" => "role-group",
            "id" => $this->resource->id,
            "attributes" => [
                'name' => $this->resource->name,
                'slug' => $this->resource->slug,
                'description' => $this->resource->description,
                $this->mergeWhen($request->routeIs('role-groups.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ])
            ]
        ];
    }
}
