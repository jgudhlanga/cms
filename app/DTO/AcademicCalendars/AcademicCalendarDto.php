<?php

namespace App\DTO\AcademicCalendars;

use App\Http\Requests\AcademicCalendars\AcademicCalendarRequest;

readonly class AcademicCalendarDto
{
    public function __construct(
        public string  $name,
        public string  $calendar_year,
        public string  $calendar_type,
        public string  $opening_date,
        public string  $closing_date,
        public ?string $description
    )
    {
    }


    public static function fromAcademicCalendarRequest(AcademicCalendarRequest $request): AcademicCalendarDto
    {
        return new self(
            name: $request->name,
            calendar_year: $request->calendar_year,
            calendar_type: $request->calendar_type,
            opening_date: $request->opening_date,
            closing_date: $request->closing_date,
            description: $request->description,
        );
    }
}
