<?php

namespace App\Http\Resources\AcademicCalendars;

use App\Http\Resources\Institution\IntakePeriodResource;
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
                'academicCalendarOptionId' => $this->academic_calendar_option_id,
                'name' => $this->academicCalendarOption?->name,
                'calendarYear' => $this->calendar_year,
                'openingDate' => $this->opening_date,
                'closingDate' => $this->closing_date,
            ],
            'relationships' => [
                'intakePeriods' => IntakePeriodResource::collection($this->intake_periods),
            ],
        ];
    }
}
