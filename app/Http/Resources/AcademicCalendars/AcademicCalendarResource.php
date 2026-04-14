<?php

namespace App\Http\Resources\AcademicCalendars;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademicCalendarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'academic-calendars',
            'id' => $this->id,
            'attributes' => [
                'calendarYear' => $this->calendar_year,
                'openingDate' => $this->opening_date,
                'closingDate' => $this->closing_date,
            ],
        ];
    }
}
