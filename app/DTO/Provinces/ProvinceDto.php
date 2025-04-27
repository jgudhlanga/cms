<?php

namespace App\DTO\Provinces;

use App\Http\Requests\Provinces\ProvinceRequest;

class ProvinceDto
{
    public function __construct(
        public readonly string $title,
        public readonly? string $description,
    )
    {
    }


    public static function fromProvinceRequest(ProvinceRequest $request): ProvinceDto
    {
        return new self(
            title: $request->title,
            description: $request->description,
        );
    }
}
