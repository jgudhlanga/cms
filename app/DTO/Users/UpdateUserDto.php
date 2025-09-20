<?php

namespace App\DTO\Users;

use App\Http\Requests\Users\UpdateUserRequest;

readonly class UpdateUserDto
{
    public function __construct(
        public string  $first_name,
        public ?string $middle_name,
        public string  $last_name,
        public string  $email,
        public ?string $phone_number,
    )
    {
    }


    public static function fromUpdateUserRequest(UpdateUserRequest $request): UpdateUserDto
    {
        return new self(
            first_name: $request->first_name,
            middle_name: $request?->middle_name,
            last_name: $request->last_name,
            email: $request->email,
            phone_number: $request->phone_number,
        );
    }
}
