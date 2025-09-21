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
        public ?string $phone_number,
        public string  $password,
        public ?array  $role_ids,
    )
    {
    }


    public static function fromUserRequest(UserRequest $request, int $tenantId, int $statusId): UserDto
    {
        return new self(
            tenant_id: $tenantId,
            status_id: $statusId,
            first_name: $request->first_name,
            middle_name: $request?->middle_name,
            last_name: $request->last_name,
            email: $request->email,
            phone_number: $request->phone_number,
            password: $request->password,
            role_ids: $request->role_ids,
        );
    }


}
