<?php

namespace App\DTO\Applications;

use App\Http\Requests\AddressTypes\AddressTypeRequest;
use App\Http\Requests\Applications\ApplicationRequest;

readonly class ApplicationDto
{
    public function __construct(
        public int     $title_id,
        public string  $first_name,
        public ?string $middle_name,
        public string  $last_name,
        public int     $gender_id,
        public string  $email,
        public string  $password,
    )
    {
    }


    public static function fromApplicationRequest(ApplicationRequest $request): ApplicationDto
    {
        return new self(
            title_id: $request->title_id,
            first_name: $request->first_name,
            middle_name: $request->middle_name,
            last_name: $request->last_name,
            gender_id: $request->gender_id,
            email: $request->email,
            password: $request->password,
        );
    }
}
