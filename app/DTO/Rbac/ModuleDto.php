<?php

namespace App\DTO\Rbac;

use App\Http\Requests\Rbac\ModuleRequest;

readonly class ModuleDto
{
	public function __construct(
		public string  $title,
		public ?string $description
	)
	{
	}


	public static function fromModuleRequest(ModuleRequest $request): ModuleDto
	{
		return new self(
			title: $request->title,
			description: $request->description,
		);
	}
}
