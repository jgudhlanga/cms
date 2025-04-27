<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\SubjectRequest;

readonly class SubjectDto
{
    public function __construct(
        public string  $name,
        public ?string $description,
    )
    {
    }


    public static function fromSubjectRequest(SubjectRequest $request): SubjectDto
    {
        return new self(
            name: $request->name,
            description: $request->description,
        );
    }
}
