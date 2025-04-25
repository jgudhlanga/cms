<?php

namespace App\Http\Resources\Shared;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{

	public function toArray(Request $request): array
	{
		return [
			'type' => 'contact',
			'id' => $this->id,
			'attributes' => [
				'name' => $this->name,
				'phoneNumber' => $this->phone_number,
				'altPhoneNumber' => $this->alt_phone_number,
				'emailAddress' => $this->email_address,
				'altEmailAddress' => $this->alt_email_address,
				'contactIsMain' => $this->contact_is_main,
				'createdAt' => $this->created_at,
				'updatedAt' => $this->updated_at,
				'deletedAt' => $this->deleted_at,
			]
		];
	}
}
