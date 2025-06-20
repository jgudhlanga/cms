<?php

namespace App\DTO\Shared;

use App\Http\Requests\Shared\NextOfKinRequest;

readonly class NextOfKinDto
{
    public function __construct(
        public string  $next_of_kin_name,
        public int     $relationship_id,
        public string  $next_of_kin_phone_number,
        public string  $next_of_kin_address_1,
        public string  $next_of_kin_address_2,
        public string  $next_of_kin_address_3,
        public ?string $next_of_kin_address_4,
    )
    {
    }

    public static function fromNextOfKinRequest(NextOfKinRequest $request): NextOfKinDto
    {
        return new self(
            next_of_kin_name: $request->next_of_kin_name,
            relationship_id: $request->relationship_id,
            next_of_kin_phone_number: $request->next_of_kin_phone_number,
            next_of_kin_address_1: $request->next_of_kin_address_1,
            next_of_kin_address_2: $request->next_of_kin_address_2,
            next_of_kin_address_3: $request->next_of_kin_address_3,
            next_of_kin_address_4: $request->next_of_kin_address_4,
        );
    }
}
