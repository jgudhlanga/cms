<?php

namespace App\Http\Resources\Shared;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NextOfKinResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'next-of-kin',
            'id' => $this->id,
            'attributes' => [
                'name' => $this->name,
                'relationship' => $this?->relationship?->name,
                'relationshipId' => $this?->relationship->id ?? null,
                'phoneNumber' => null,
                'altPhoneNumber' => null,
                'emailAddress' => null,
                'altEmailAddress' => null,
                'address1' => null,
                'address2' => null,
                'address3' => null,
                'address4' => null,
                'createdAt' => $this->created_at,
                'updatedAt' => $this->updated_at,
                'deletedAt' => $this->deleted_at,
            ]
        ];
    }
}
