<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\DivisionRequest;

readonly class DivisionDto
{
    public function __construct(
        public string $name,
        public ?string $description,
        public ?int $headOfDivisionId,
    ) {}

    public static function fromDivisionRequest(DivisionRequest $request): DivisionDto
    {
        return new self(
            name: $request->string('name')->toString(),
            description: $request->filled('description') ? $request->string('description')->toString() : null,
            headOfDivisionId: $request->filled('head_of_division_id') ? $request->integer('head_of_division_id') : null,
        );
    }
}
