<?php

namespace App\Http\Resources\AcademicCalendars;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademicCalendarResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            "type" => "academic-calendars",
            "id" => $this->resource->id,
            "attributes" => [
                'name' => $this->resource->name,
                'calendarType' => $this->resource->calendar_type,
                'calendarYear' => $this->resource->calendar_year,
                'openingDate' => $this->resource->opening_date,
                'closingDate' => $this->resource->closing_date,
                'description' => $this->resource->description,
                $this->mergeWhen($request->routeIs('academic-calendars.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ])
            ],
        ];
    }
}
