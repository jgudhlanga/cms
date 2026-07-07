<?php

namespace App\Http\Resources\Institution;

use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssessmentCalendarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $type = $this->resource->type;
        $typeValue = $type instanceof BackedEnum ? $type->value : $type;

        return [
            'type' => 'assessment-calendar',
            'id' => $this->resource->id,
            'attributes' => [
                'assessmentTypeId' => $this->resource->assessment_type_id,
                'academicCalendarId' => $this->resource->academic_calendar_id,
                'academicCalendarName' => $this->when(
                    $this->resource->relationLoaded('academicCalendar') && $this->resource->academicCalendar,
                    fn () => $this->resource->academicCalendar->calendar_year.' ('.$this->resource->academicCalendar->opening_date.' - '.$this->resource->academicCalendar->closing_date.')',
                ),
                'startDate' => $this->resource->start_date?->format('Y-m-d'),
                'endDate' => $this->resource->end_date?->format('Y-m-d'),
                'type' => $typeValue,
                'typeLabel' => is_string($typeValue) ? ucfirst($typeValue) : $typeValue,
                $this->mergeWhen($request->routeIs('assessment-calendars.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ]),
            ],
        ];
    }
}
