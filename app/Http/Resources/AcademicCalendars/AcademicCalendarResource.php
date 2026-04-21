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
        $openingDate = Carbon::parse($this->opening_date);
        $closingDate = Carbon::parse($this->closing_date);
        $calendarType = $this->type instanceof BackedEnum ? $this->type->value : $this->type;
        $calendarPeriodLabel = $this->resolveCalendarPeriodLabel($calendarType, $openingDate, $closingDate);
        $formattedOpeningDate = $openingDate->format('j F');
        $formattedClosingDate = $closingDate->format('j F Y');

        return [
            'type' => 'academic-calendars',
            'id' => $this->id,
            'attributes' => [
                'name' => "{$calendarPeriodLabel} - ({$formattedOpeningDate} - {$formattedClosingDate})",
                'calendarYear' => $this->calendar_year,
                'type' => is_string($calendarType) && $calendarType !== '' ? $calendarType : 'semester',
                'openingDate' => $this->opening_date,
                'closingDate' => $this->closing_date,
            ],
        ];
    }

    private function resolveCalendarPeriodLabel(mixed $calendarType, Carbon $openingDate, Carbon $closingDate): string
    {
        if (! is_string($calendarType) || $calendarType === '') {
            return 'Semester 1';
        }

        if ($calendarType === 'semester') {
            return 'Semester '.$this->resolveSemesterNumber($openingDate, $closingDate);
        }

        if ($calendarType === 'term') {
            return 'Term '.$this->resolveTermNumber($openingDate, $closingDate);
        }

        if ($calendarType === 'abma') {
            return 'Term '.$this->resolveAbmaTermNumber($openingDate, $closingDate);
        }

        return ucfirst($calendarType);
    }

    private function resolveSemesterNumber(Carbon $openingDate, Carbon $closingDate): int
    {
        $averageMonth = (int) round(($openingDate->month + $closingDate->month) / 2);

        return $averageMonth <= 6 ? 1 : 2;
    }

    private function resolveTermNumber(Carbon $openingDate, Carbon $closingDate): int
    {
        $averageMonth = (int) round(($openingDate->month + $closingDate->month) / 2);

        return match (true) {
            $averageMonth <= 4 => 1,
            $averageMonth <= 8 => 2,
            default => 3,
        };
    }

    private function resolveAbmaTermNumber(Carbon $openingDate, Carbon $closingDate): int
    {
        $averageMonth = (int) round(($openingDate->month + $closingDate->month) / 2);

        return match (true) {
            $averageMonth <= 3 => 1,
            $averageMonth <= 6 => 2,
            $averageMonth <= 9 => 3,
            default => 4,
        };
    }
}
