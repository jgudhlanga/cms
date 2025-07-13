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
                'phoneNumber' => $this->firstContact?->phone_number ?? null,
                'altPhoneNumber' => $this->firstContact?->alt_phone_number ?? null,
                'emailAddress' => $this->firstContact?->email_address ?? null,
                'altEmailAddress' => $this->firstContact?->alt_email_address ?? null,
                'address1' => $this->firstAddress?->address_1 ?? null,
                'address2' => $this->firstAddress?->address_2 ?? null,
                'address3' => $this->firstAddress?->address_3 ?? null,
                'address4' => $this->firstAddress?->address_4 ?? null,
                'createdAt' => $this->created_at,
                'updatedAt' => $this->updated_at,
                'deletedAt' => $this->deleted_at,
            ]
        ];
    }
}
