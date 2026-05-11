<?php

namespace App\Http\Resources\HMS;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HostelRoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'hostel_room',
            'id'   => $this->resource->id,
            'attributes' => [
                'hostelId'    => $this->resource->hostel_id,
                'hostelName'  => $this->resource->hostel?->name,
                'name'  => $this->resource->name,
                'roomType'    => $this->resource->room_type,
                'capacity'    => $this->resource->capacity,
                'status'      => $this->resource->status,
                'maxOccupancy'=> $this->resource->max_occupancy,
                "occupancy" => '0/0',
                'floorNumber' => $this->resource->floor_number,
                'description' => $this->resource->description,
                'createdAt'   => $this->resource->created_at,
                'updatedAt'   => $this->resource->updated_at,
                'deletedAt'   => $this->resource->deleted_at,
            ],
        ]; 
    }
}
