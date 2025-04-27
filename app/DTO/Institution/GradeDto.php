<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\GradeRequest;

readonly class GradeDto
{
    public function __construct(
        public string  $name,
        public ?string $description,
    )
    {
    }


    public static function fromGradeRequest(GradeRequest $request): GradeDto
    {
        return new self(
            name: $request->name,
            description: $request->description,
        );
    }
}
