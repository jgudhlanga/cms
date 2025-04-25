<?php

namespace App\DTO\Shared;

use App\Http\Requests\Shared\ContactRequest;

readonly class ContactDto
{
	public function __construct(
		public string  $name,
		public string  $phone_number,
		public ?string  $alt_phone_number,
		public string $email_address,
		public ?string $alt_email_address,
		public ?bool   $contact_is_main,
	)
	{
	}

	public static function fromContactRequest(ContactRequest $request): ContactDto
	{
		return new self(
			name: $request->name,
			phone_number: $request->phone_number,
			alt_phone_number: $request->alt_phone_number,
			email_address: $request->email_address,
			alt_email_address: $request->alt_email_address,
			contact_is_main: $request->contact_is_main ?? false,
		);
	}
}
