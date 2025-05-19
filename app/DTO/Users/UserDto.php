<?php

namespace App\DTO\Users;

use App\Http\Requests\Users\CreateUserRequest;
use App\Models\Tenants\Tenant;

readonly class UserDto
{
    public function __construct(
        public int     $tenant_id,
        public int     $title_id,
        public string  $first_name,
        public ?string $middle_name,
        public string  $last_name,
        public int     $gender_id,
        public ?int    $race_id,
        public string  $email,
        public string  $password,
    )
    {
    }


    public static function fromCreateUserRequest(CreateUserRequest $request, Tenant $tenant): UserDto
    {
        return new self(
            tenant_id: $tenant->id,
            title_id: $request->title_id,
            first_name: $request->first_name,
            middle_name: $request->middle_name,
            last_name: $request->last_name,
            gender_id: $request->gender_id,
            race_id: $request->race_id,
            email: $request->email,
            password: $request->password,
        );
    }
}
