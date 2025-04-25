<?php

namespace App\DTO\Acl;

use App\Http\Requests\Acl\PermissionRequest;
use App\Http\Requests\Acl\RoleRequest;

readonly class RoleDto
{
	public function __construct(
		public string   $name,
		public ? string $description,
		public readonly? array $permissions,
	)
	{
	}


	public static function fromRoleRequest(RoleRequest $request): RoleDto
	{
		return new self(
			name: $request->name,
			description: $request->description, 
			permissions: (!is_null($request->permissions) && is_string($request->permissions)) ? json_decode($request->permissions, true): $request->permissions,
		);
	}
}
