<?php

namespace App\Http\Resources\Institution;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentLevelCourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing([
            'departmentLevel.level',
            'departmentCourse.course',
        ]);

        $calendarType = $this->departmentLevel?->level?->calendar_type;
        $calendarTypeValue = $calendarType instanceof AcademicCalendarTypeEnum
            ? $calendarType->value
            : (is_string($calendarType) && $calendarType !== '' ? $calendarType : 'semester');

        return [
            'id' => $this->id,
            'departmentCourseId' => $this?->department_course_id,
            'departmentLevelId' => $this?->department_level_id,
            'level' => $this?->departmentLevel?->level?->name,
            'calendarType' => $calendarTypeValue,
            'course' => $this?->departmentCourse?->course?->name,
            'durationYears' => (float) $this->duration_years,
            'taughtSemesterCount' => $this->taught_semester_count,
            'includesIndustrialAttachment' => (bool) $this->includes_industrial_attachment,
            'attachmentSemesterCount' => $this->attachment_semester_count,
            'programmeSemesters' => $this->whenLoaded('programmeSemesters', fn () => $this->programmeSemesters->map(fn ($semester) => [
                'id' => $semester->id,
                'position' => $semester->position,
                'name' => $semester->name,
                'kind' => $semester->kind?->value ?? $semester->kind,
            ])->values()),
            'hasEnrolmentRequirements' => $this->departmentCourse?->course?->has_enrolment_requirements,
        ];
    }
}
