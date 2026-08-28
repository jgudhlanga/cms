<?php

declare(strict_types=1);

namespace App\Http\Resources\Maintenance;

use App\Models\Students\StudentEnrolment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StudentEnrolment
 */
class StudentEnrolmentExportPreviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'maintenance-student-enrolment-export-preview',
            'id' => $this->id,
            'attributes' => [
                'studentId' => $this->student_id,
                'name' => $this->student?->user?->full_name,
                'studentNumber' => $this->student?->student_number,
                'gender' => $this->student?->gender?->title,
                'department' => $this->institutionDepartment?->department?->name,
                'level' => $this->departmentLevel?->level?->name,
                'course' => $this->departmentCourse?->course?->name,
                'modeOfStudy' => $this->modeOfStudy?->name,
                'semester' => $this->semester?->name,
                'calendarYear' => $this->academicCalendar?->calendar_year,
                'calendarType' => $this->academicCalendar?->type?->value,
            ],
        ];
    }
}
