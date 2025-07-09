<?php

namespace App\DTO\Acl;

use App\Http\Requests\Acl\RoleGroupRequest;

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
