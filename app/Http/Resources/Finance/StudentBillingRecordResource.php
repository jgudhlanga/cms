<?php

declare(strict_types=1);

namespace App\Http\Resources\Finance;

use App\Support\AcademicCalendars\AcademicCalendarPeriodResolver;
use App\Support\Institution\ProgrammeSemesterNameFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentBillingRecordResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $student = $this->resource->student;
        $user = $student?->user;
        $enrolment = $this->resource->enrolment;
        $levelName = $enrolment?->departmentLevel?->level?->name;
        $billedBy = $this->resource->billedBy;
        $calendar = $this->resource->academicCalendar;

        $nameParts = array_filter([
            $user?->last_name,
            $user?->first_name,
        ]);

        return [
            'type' => 'studentBillingRecord',
            'id' => $this->resource->id,
            'attributes' => [
                'studentId' => $this->resource->student_id,
                'studentNumber' => $this->resource->student_number ?? $student?->student_number,
                'studentName' => $nameParts !== [] ? implode(' ', $nameParts) : null,
                'department' => $enrolment?->institutionDepartment?->department?->name,
                'level' => $levelName,
                'course' => $enrolment?->departmentCourse?->course?->name,
                'phase' => $this->resource->programmeSemester !== null
                    ? ProgrammeSemesterNameFormatter::qualifiedName($levelName, $this->resource->programmeSemester->name)
                    : null,
                'billingPeriod' => $calendar !== null
                    ? trim((string) $calendar->calendar_year).' · '.AcademicCalendarPeriodResolver::displayPeriodLabel($calendar)
                    : null,
                'status' => $this->resource->status?->value,
                'statusLabel' => $this->resource->status?->label(),
                'pastelLinked' => (bool) ($this->resource->pastel_link_exists ?? false),
                'exportedAt' => $this->resource->exported_at?->toIso8601String(),
                'billedAt' => $this->resource->billed_at?->toIso8601String(),
                'billedByName' => $billedBy !== null
                    ? trim(($billedBy->first_name ?? '').' '.($billedBy->last_name ?? ''))
                    : null,
                'createdAt' => $this->resource->created_at?->toIso8601String(),
            ],
        ];
    }
}
