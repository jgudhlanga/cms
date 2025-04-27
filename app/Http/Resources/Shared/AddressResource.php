<?php

namespace App\Http\Resources\Shared;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
			'type' => 'address',
			'id' => $this->id,
			'attributes' => [
				'address1' => $this->address_1,
				'address2' => $this->address_2,
				'address3' => $this->address_3,
				'address4' => $this->address_4,
				'address5' => $this->address_5,
				'address6' => $this->address_6,
				'addressIsMain' => $this->address_is_main,
				'createdAt' => $this->created_at,
				'updatedAt' => $this->updated_at,
				'deletedAt' => $this->deleted_at,
			]
		];
    }
}
