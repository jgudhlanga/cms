<?php

namespace App\DTO\Applications;

use App\Http\Requests\Applications\ApplicationRequest;

readonly class ApplicationDto
{
    public function __construct(
        public int $user_id,

    )
    {
    }


    public static function fromApplicationRequest(ApplicationRequest $request): ApplicationDto
    {
        return new self(
            user_id: $request->user_id,
        );
    }
}
