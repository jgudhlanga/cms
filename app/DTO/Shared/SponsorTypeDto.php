<?php

namespace App\DTO\Shared;

use App\Http\Requests\Shared\SponsorTypeRequest;

readonly class SponsorTypeDto
{
    public function __construct(
        public string   $name,
		public ? string $description,
    )
    {
    }


    public static function fromSponsorTypeRequest(SponsorTypeRequest $request): SponsorTypeDto
    {
        return new self(
            name: $request->name,
			description: $request->description,
        );
    }
}
