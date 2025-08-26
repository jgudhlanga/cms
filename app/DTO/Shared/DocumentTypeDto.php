<?php

namespace App\DTO\Shared;

use App\Http\Requests\Shared\DocumentTypeRequest;

readonly class DocumentTypeDto
{
    public function __construct(
        public string  $name,
        public ?string $description,
    )
    {
    }


    public static function fromDocumentTypeRequest(DocumentTypeRequest $request): DocumentTypeDto
    {
        return new self(
            name: $request->name,
            description: $request->description,
        );
    }
}
