<?php

namespace App\Http\Resources\Students;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SponsorResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        $contact = $this->contacts->first() ?? null;
        $address = $this->addresses->first() ?? null;
        return [
            'type' => 'sponsor',
            'id' => $this->id,
            'attributes' => [
                'name' => $this->name,
                'sponsorTypeId' => $this->sponsor_type_id,
                'sponsorType' => $this?->sponsorType?->name,
                'phoneNumber' => $contact?->phone_number ?? null,
                'email' => $contact?->email_address ?? null,
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
