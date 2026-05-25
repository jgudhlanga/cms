<?php

namespace App\Http\Resources\AcademicCalendars;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Support\AcademicCalendars\AcademicCalendarPeriodResolver;
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
        $calendarPeriodLabel = $this->resolveCalendarPeriodLabel($calendarType, $openingDate);
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

    private function resolveCalendarPeriodLabel(mixed $calendarType, Carbon $openingDate): string
    {
        if (! is_string($calendarType) || $calendarType === '') {
            return 'Semester 1';
        }

        $enum = AcademicCalendarTypeEnum::tryFrom($calendarType);

        if (! $enum instanceof AcademicCalendarTypeEnum) {
            return ucfirst($calendarType);
        }

        if ($this->resource instanceof AcademicCalendar) {
            return AcademicCalendarPeriodResolver::displayPeriodLabel($this->resource);
        }

        return AcademicCalendarPeriodResolver::displayPeriodLabelFromOpening($enum, $openingDate);
    }
}
