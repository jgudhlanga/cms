<?php

namespace App\Http\Resources\AcademicCalendars;

use App\Models\Institution\Syllabus\CourseSyllabus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassConfigResource extends JsonResource
{
    public function toArray(Request $request): array
    {
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
                'courseSyllabusIds' => $syllabusIds,
                'courseSyllabusCodes' => $codesOrdered,
            ],
        ];
    }
}
