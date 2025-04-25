<?php

namespace App\DTO\Users;

use App\Http\Requests\Users\CreateUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;

class UserDto
{
	public function __construct(
		public readonly string  $name,
		public readonly string  $email,
		public readonly ?string $password,
		public readonly mixed   $meta,
	)
	{
	}

	public static function fromCreateUserRequest(CreateUserRequest $request): UserDto
	{
		return new self(
			name: $request->name,
			email: $request->email,
			password: $request->password,
			meta: null,
		);
	}

	public static function fromUpdateUseRequest(UpdateUserRequest $request): UserDto
	{
		return new self(
			name: $request->name,
			email: $request->email,
			password: $request->password,
			meta: null,
		);
	}
}
