<?php


namespace App\DTO\Auth;

use Illuminate\Http\Request;

class RegistrationDto
{
	public function __construct(
		public readonly string $name,
		public readonly string $email,
		public readonly string $password,
		public readonly mixed  $meta,
	)
	{
	}


	public static function fromApiRegisterRequest(Request $request): RegistrationDto
	{
		return new self(
            name: $request->name,
            email: $request->email,
            password: $request->password,
            meta: null,
        );
    }
}
