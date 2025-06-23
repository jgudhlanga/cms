<?php

namespace App\DTO\Districts;

use App\Http\Requests\Shared\DistrictRequest;

readonly class DistrictDto
{
    public function __construct(
        public string   $name,
        public ? int $province_id,
        public ? string $description,
    )
    {
    }


    public static function fromDistrictRequest(DistrictRequest $request): DistrictDto
    {
        return new self(
            name: $request->name,
            province_id: $request->province_id,
            description: $request->description,
        );
    }
}
