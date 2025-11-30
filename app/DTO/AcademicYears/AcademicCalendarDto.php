<?php

namespace App\DTO\AcademicYears;

use App\Http\Requests\AcademicYears\AcademicYearRequest;

readonly class AcademicYearDto
{
    public function __construct(
        public string  $name,
        public string  $type,
        public string  $year,
        public string  $start_date,
        public string  $end_date,
        public ?string $description
    )
    {
    }


    public static function fromAcademicYearRequest(AcademicYearRequest $request): AcademicYearDto
    {
        return new self(
            name: $request->input('name'),
            type: $request->input('type'),
            year: $request->input('year'),
            start_date: $request->input('start_date'),
            end_date: $request->input('end_date'),
            description: $request->input('description'),
        );
    }
}
