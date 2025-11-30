<?php

namespace App\DTO\AcademicYears;

use App\Http\Requests\AcademicCalendars\AcademicCalendarRequest;

readonly class AcademicCalendarDto
{
    public function __construct(
        public string  $name,
        public string  $type,
        public string  $year,
        public string  $opening_date,
        public string  $closing_date,
        public ?string $description
    )
    {
    }


    public static function fromAcademicCalendarRequest(AcademicCalendarRequest $request): AcademicCalendarDto
    {
        return new self(
            name: $request->input('name'),
            type: $request->input('type'),
            year: $request->input('year'),
            opening_date: $request->input('opening_date'),
            closing_date: $request->input('closing_date'),
            description: $request->input('description'),
        );
    }
}
