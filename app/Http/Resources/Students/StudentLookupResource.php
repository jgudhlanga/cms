<?php

declare(strict_types=1);

namespace App\Http\Resources\Students;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentLookupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $enrolment = $this->latestEnrolment;

        return [
            'studentId' => $this->id,
            'studentName' => $this->user?->full_name,
            'studentNumber' => $this->student_number,
            'idNumber' => $this->id_number,
            'department' => $enrolment?->institutionDepartment?->department?->name,
            'level' => $enrolment?->departmentLevel?->level?->name,
            'course' => $enrolment?->departmentCourse?->course?->name,
            'modeOfStudy' => $enrolment?->modeOfStudy?->name,
        ];
    }
}
