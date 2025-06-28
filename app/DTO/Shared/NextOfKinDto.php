<?php

namespace App\DTO\Shared;

use App\Http\Requests\Shared\NextOfKinRequest;

readonly class NextOfKinDto
{
    public function __construct(
        public string  $name,
        public int     $relationship_id,
    )
    {
    }

    public static function fromNextOfKinRequest(NextOfKinRequest $request): NextOfKinDto
    {
        return new self(
            name: $request->next_of_kin_name,
            relationship_id: $request->relationship_id,
        );
    }
}
