<?php

namespace App\DTO\Languages;

use App\Http\Requests\Shared\LanguageRequest;

readonly class LanguageDto
{
	public function __construct(
		public string   $title,
		public ? string $description,
	)
	{
	}


	public static function fromLanguageRequest(LanguageRequest $request): LanguageDto
	{
		return new self(
			title: $request->title,
			description: $request->description,
		);
	}
}
