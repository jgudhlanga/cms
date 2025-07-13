<?php

namespace App\DTO\Shared;

use App\Http\Requests\Shared\NextOfKinRequest;

readonly class NextOfKinDto
{
    public function __construct(
        public string  $name,
        public int     $relationship_id,
        public string  $phone_number,
        public string  $address_1,
        public string  $address_2,
        public string  $address_3,
        public ?string $address_4,
    )
    {
    }

    public static function fromNextOfKinRequest(NextOfKinRequest $request): NextOfKinDto
    {
        return new self(
            name: $request->next_of_kin_name,
            relationship_id: $request->relationship_id,
            phone_number: $request->phone_number,
            address_1: $request->address_1,
            address_2: $request->address_2,
            address_3: $request->address_3,
            address_4: $request->address_4,
        );
    }
}
