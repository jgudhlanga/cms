<?php

namespace App\DTO\Shared;

use App\Http\Requests\Shared\TitleRequest;

readonly class TitleDto
{
    public function __construct(
        public string   $name,
		public ? string $description,
    )
    {
    }


    public static function fromTitleRequest(TitleRequest $request): TitleDto
    {
        return new self(
            name: $request->name,
			description: $request->description,
        );
    }
}
