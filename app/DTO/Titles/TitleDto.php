<?php

namespace App\DTO\Titles;

use App\Http\Requests\Titles\TitleRequest;

class TitleDto
{
    public function __construct(
        public readonly string $name,
		public readonly? string $description,
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
