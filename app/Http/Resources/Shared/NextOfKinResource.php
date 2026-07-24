<?php

namespace App\Http\Resources\Shared;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NextOfKinResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['relationship', 'contacts', 'addresses']);

        $contact = $this->contacts->first();
        $address = $this->addresses->first();

        return [
            'type' => 'next-of-kin',
            'id' => $this->id,
            'attributes' => [
                'name' => $this->name,
                'relationship' => $this?->relationship?->name,
                'relationshipId' => $this?->relationship->id ?? null,
                'phoneNumber' => $contact?->phone_number ?? null,
                'altPhoneNumber' => $contact?->alt_phone_number ?? null,
                'emailAddress' => $contact?->email_address ?? null,
                'altEmailAddress' => $contact?->alt_email_address ?? null,
                'address1' => $address?->address_1 ?? null,
                'address2' => $address?->address_2 ?? null,
                'address3' => $address?->address_3 ?? null,
                'address4' => $address?->address_4 ?? null,
                'createdAt' => $this->created_at,
                'updatedAt' => $this->updated_at,
                'deletedAt' => $this->deleted_at,
            ]
        ];
    }
}
