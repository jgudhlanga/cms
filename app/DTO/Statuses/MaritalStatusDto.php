<?php

namespace App\DTO\Statuses;

use App\Http\Requests\Statuses\MaritalStatusRequest;

readonly class MaritalStatusDto
{
    public function __construct(
        public string   $title,
		public ? string $description,
    )
    {
    }


    public static function fromMaritalStatusRequest(MaritalStatusRequest $request): MaritalStatusDto
    {
        return new self(
            title: $request->title,
			description: $request->description,
        );
    }
}
