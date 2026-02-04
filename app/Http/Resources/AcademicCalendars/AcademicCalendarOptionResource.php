<?php

namespace App\Http\Resources\AcademicCalendars;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademicCalendarOptionResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'academic-calendar-options',
            'id' => $this->id,
            'attributes' => [
                'name' => $this->name,
                'description' => $this->description,
            ],
        ];
    }
}
