<?php

namespace App\DTO\Shared;


use App\Http\Requests\Shared\CommunicationMethodRequest;

class CommunicationMethodDto
{
    public function __construct(
        public readonly string $title,
    )
    {
    }


    public static function fromCommunicationMethodRequest( CommunicationMethodRequest $request): CommunicationMethodDto
    {
        return new self(
            title: $request->title,
        );
    }
}
