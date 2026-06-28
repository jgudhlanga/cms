<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\IntakePeriodRequest;

readonly class IntakePeriodDto
{
    public function __construct(
        public string  $name,
        public string  $start_date,
        public string  $end_date,
        public ?string $description,
        public string  $status,
    )
    {
    }


    public static function fromIntakePeriodRequest(IntakePeriodRequest $request): IntakePeriodDto
    {
        return new self(
            name: $request->name,
            start_date: $request->start_date,
            end_date: $request->end_date,
            description: $request->description,
            status: $request->status,
        );
    }
}
