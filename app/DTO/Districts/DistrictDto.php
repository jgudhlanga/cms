<?php

namespace App\DTO\Districts;

use App\Http\Requests\Districts\DistrictRequest;

readonly class DistrictDto
{
    public function __construct(
        public string   $title,
        public ? string $description,
    )
    {
    }


    public static function fromDistrictRequest(DistrictRequest $request): DistrictDto
    {
        return new self(
            title: $request->title,
            description: $request->description,
        );
    }
}
