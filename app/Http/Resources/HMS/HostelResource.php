<?php

namespace App\Http\Resources\HMS;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Institution\StaffResource;

class HostelResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'hostel',
            'id' => $this->resource->id,
            'attributes' => [
                'name' => $this->resource->name,
                'type' => $this->resource->type,
                'capacity' => $this->resource->capacity,
                'roomsCount' => $this->resource->rooms_count,
                'floorCount' => $this->resource->floor_count,
                'status' => $this->resource->status,
                'location' => $this->resource->location,
                'description' => $this->resource->description,
                'occupiedCount' => 0,
                'vacantCount' => 0,
                'maintenanceCount' => 0,
                'wardenId' => $this->resource->warden_id,
                'warden' => StaffResource::make($this->resource->warden),
                'createdAt' => $this->resource->created_at,
                'updatedAt' => $this->resource->updated_at,
                'deletedAt' => $this->resource->deleted_at,
            ],
        ];
    }
}