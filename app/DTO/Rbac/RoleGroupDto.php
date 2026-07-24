<?php

namespace App\DTO\Rbac;

use App\Http\Requests\Rbac\RoleGroupRequest;

readonly class RoleGroupDto
{
    public function __construct(
        public string  $name,
        public ?string $description,
    )
    {
    }


    public static function fromRoleGroupRequest(RoleGroupRequest $request): RoleGroupDto
    {
        return new self(
            name: $request->name,
            description: $request->description,
        );
    }
}
