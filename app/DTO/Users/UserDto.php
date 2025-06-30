<?php

namespace App\DTO\Users;

use App\Http\Requests\Users\UserRequest;
use App\Models\Shared\Status;
use App\Models\Tenants\Tenant;

readonly class UserDto
{
    public function __construct(
        public int     $tenant_id,
        public int     $status_id,
        public string  $first_name,
        public ?string $middle_name,
        public string  $last_name,
        public string  $email,
        public string  $password,
    )
    {
    }


    public static function fromUserRequest(UserRequest $request, ?Tenant $tenant = null, ?Status $status = null): UserDto
    {
        return new self(
            tenant_id: $tenant?->id,
            status_id: $status?->id,
            first_name: $request->first_name,
            middle_name: $request->middle_name,
            last_name: $request->last_name,
            email: $request->email,
            password: $request->password,
        );
    }
}
