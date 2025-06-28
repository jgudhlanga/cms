<?php

namespace App\DTO\Shared;

use App\Http\Requests\Shared\RelationshipRequest;

readonly class RelationshipDto
{
    public function __construct(
        public string  $name,
        public ?string $description,
    )
    {
    }


    public static function fromRelationshipRequest(RelationshipRequest $request): RelationshipDto
    {
        return new self(
            name: $request->name,
            description: $request->description,
        );
    }
}
