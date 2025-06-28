<?php

namespace App\DTO\Students;

use App\Http\Requests\Students\SponsorRequest;
use App\Models\Students\Student;

readonly class SponsorDto
{
    public function __construct(
        /** Personal details */
        public string  $name,
        public int     $student_id,
        public int     $sponsor_type_id,
        /** Contact details, addresses */
        public ?string $phone_number,
        public ?string $email,
        public ?string $address_1,
        public ?string $address_2,
        public ?string $address_3,
        public ?string $address_4,
    )
    {
    }

    public static function fromSponsorRequest(SponsorRequest $request, Student $student): SponsorDto
    {
        return new self(
        /** Personal details */
            name: $request->name,
            student_id: $student->id,
            sponsor_type_id: $request->sponsor_type_id,
            /** Contact details, addresses */
            phone_number: $request->phone_number,
            email: $request->email,
            address_1: $request->address_1,
            address_2: $request->address_2,
            address_3: $request->address_3,
            address_4: $request->address_4,
        );
    }
}
