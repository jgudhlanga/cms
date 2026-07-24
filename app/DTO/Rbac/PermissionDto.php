<?php

namespace App\DTO\Rbac;

use App\Http\Requests\Rbac\PermissionRequest;

readonly class PermissionDto
{
	public function __construct(
		public string   $name,
		public ? string $description,
		public ? string $module_id,
	)
	{
	}


	public static function fromPermissionRequest(PermissionRequest $request): PermissionDto
	{
		return new self(
			name: $request->name,
			description: $request->description,
			module_id: $request->module_id,
		);
	}
}
