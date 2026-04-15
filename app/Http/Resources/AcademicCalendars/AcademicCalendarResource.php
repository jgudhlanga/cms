<?php

namespace App\Http\Resources\AcademicCalendars;

use BackedEnum;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademicCalendarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $openingDate = Carbon::parse($this->opening_date)->format('j F');
        $closingDate = Carbon::parse($this->closing_date)->format('j F Y');
        $calendarType = $this->type instanceof BackedEnum ? $this->type->value : $this->type;

        return [
            'type' => 'academic-calendars',
            'id' => $this->id,
            'attributes' => [
                'name' => "{$this->calendar_year} ({$openingDate} - {$closingDate})",
                'calendarYear' => $this->calendar_year,
                'type' => is_string($calendarType) && $calendarType !== '' ? $calendarType : 'semester',
                'openingDate' => $this->opening_date,
                'closingDate' => $this->closing_date,
            ],
        ];
    }
}
