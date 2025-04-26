<?php

namespace App\DTO\Titles;

use App\Http\Requests\Titles\TitleRequest;

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
