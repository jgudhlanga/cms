<?php

namespace App\Http\Resources\AcademicCalendars;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassConfigResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'ClassConfig',
            'id' => $this->id,
            'attributes' => [
                'studentsPerClass' => $this->students_per_class,
                'academicCalendar' => $this->academicCalendar?->academicCalendarOption?->name . ' (' . $this->academicCalendar?->calendar_year . ')',
                'institutionDepartment' => $this->institutionDepartment?->department?->name,
                'departmentCourse' => $this->departmentCourse?->course?->name,
                'departmentLevel' => $this->departmentLevel?->level?->name,
                'modeOfStudy' => $this->modeOfStudy?->name,
            ],
        ];
    }
}
