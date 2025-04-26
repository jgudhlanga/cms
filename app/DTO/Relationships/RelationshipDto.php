<?php

namespace App\DTO\Relationships;

use App\Http\Requests\Relationships\RelationshipRequest;

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
