<?php

namespace App\Http\Resources\Enrolments;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OtherApplicationResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'applicationId' => $this->id,
            'department' => $this->institutionDepartment?->department?->name,
            'level' => $this->departmentLevel?->level?->name,
            'course' => $this->departmentCourse?->course?->name,
            'modeOfStudy' => $this->modeOfStudy?->name,
            'inClassList' => $this->classList ? true : false,
        ];
    }
}
