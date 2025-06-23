<?php

namespace App\DTO\Statuses;

use App\Http\Requests\Shared\StatusRequest;

class StatusDto
{
    public function __construct(
        public readonly string $title,
		public readonly? string $description,
    )
    {
    }


    public static function fromStatusRequest(StatusRequest $request): StatusDto
    {
        return new self(
            title: $request->title,
			description: $request->description,
        );
    }
}
