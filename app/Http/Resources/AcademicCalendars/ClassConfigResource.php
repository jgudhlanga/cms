<?php

namespace App\Http\Resources\AcademicCalendars;

use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\Syllabus\CourseSyllabus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassConfigResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof ClassConfig) {
            $this->resource->loadMissing([
                'semester',
                'programmeSemester',
                'institutionDepartment.department',
                'departmentCourse.course',
                'departmentLevel.level',
                'modeOfStudy',
            ]);
        }

        $syllabusIds = array_values(array_map('intval', array_filter($this->course_syllabus_ids ?? [])));
        $codeById = $syllabusIds === []
            ? []
            : CourseSyllabus::query()->whereIn('id', $syllabusIds)->pluck('code', 'id')->all();
        $codesOrdered = [];
        foreach ($syllabusIds as $sid) {
            if (isset($codeById[$sid])) {
                $codesOrdered[] = $codeById[$sid];
            }
        }

        return [
            'type' => 'ClassConfig',
            'id' => $this->id,
            'attributes' => [
                'studentsPerClass' => $this->students_per_class,
                'calendarYear' => $this->calendar_year,
                'institutionDepartment' => $this->institutionDepartment?->department?->name,
                'departmentCourse' => $this->departmentCourse?->course?->name,
                'departmentLevel' => $this->departmentLevel?->level?->name,
                'modeOfStudy' => $this->modeOfStudy?->name,
                'semesterId' => $this->semester_id !== null ? (int) $this->semester_id : null,
                'programmeSemesterId' => $this->programme_semester_id !== null ? (int) $this->programme_semester_id : null,
                'periodLabel' => $this->periodLabel(),
                'courseSyllabusIds' => $syllabusIds,
                'courseSyllabusCodes' => $codesOrdered,
            ],
        ];
    }

    private function periodLabel(): ?string
    {
        $storedName = is_string($this->name) ? trim($this->name) : '';
        if ($storedName !== '') {
            return $storedName;
        }

        $programmeName = is_string($this->programmeSemester?->name) ? trim($this->programmeSemester->name) : '';
        if ($programmeName !== '') {
            return $programmeName;
        }

        $semesterName = is_string($this->semester?->name) ? trim($this->semester->name) : '';

        return $semesterName !== '' ? $semesterName : null;
    }
}
