<?php

namespace App\DTO\Shared;

use App\Http\Requests\Shared\AddressRequest;

readonly class AddressDto
{
	public function __construct(
		public string  $address_1,
		public string  $address_2,
		public string  $address_3,
		public ?string $address_4,
		public ?string $address_5,
		public ?string $address_6,
		public ?bool   $address_is_main,
	)
	{
	}

	public static function fromAddressRequest(AddressRequest $request): AddressDto
	{
		return new self(
			address_1: $request->address_1,
			address_2: $request->address_2,
			address_3: $request->address_3,
			address_4: $request->address_4,
			address_5: $request->address_5,
			address_6: $request->address_6,
			address_is_main: $request->address_is_main ?? false,
		);
	}
}
