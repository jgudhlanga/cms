<?php

namespace App\Http\Resources\Students;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SponsorResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'sponsor',
            'id' => $this->id,
            'attributes' => [
                'name' => $this->name,
                'sponsorTypeId' => $this->sponsor_type_id,
                'sponsorType' => $this?->sponsorType?->name,
                'address1' => $this->address_1,
                'address2' => $this->address_2,
                'address3' => $this->address_3,
                'address4' => $this->address_4,
                'createdAt' => $this->created_at,
                'updatedAt' => $this->updated_at,
                'deletedAt' => $this->deleted_at,
            ]
        ];
    }
}
