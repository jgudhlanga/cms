<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentApplication;

class CourseSyllabusCodeResolver
{
    public function resolve(?StudentEnrolment $enrolment): ?string
    {
        if ($enrolment === null) {
            return null;
        }

        $syllabusIds = $this->resolveSyllabusIds($enrolment);

        if ($syllabusIds === []) {
            return null;
        }

        return $this->resolveCodeFromSyllabusIds($syllabusIds);
    }

    public function resolveForProgram(?StudentApplication $program): ?string
    {
        if ($program === null) {
            return null;
        }

        $syllabusIds = $this->resolveSyllabusIdsForProgram($program);

        if ($syllabusIds === []) {
            return null;
        }

        return $this->resolveCodeFromSyllabusIds($syllabusIds);
    }

    /**
     * @param  list<int>  $syllabusIds
     */
    private function resolveCodeFromSyllabusIds(array $syllabusIds): ?string
    {
        return CourseSyllabus::query()
            ->whereIn('id', $syllabusIds)
            ->orderBy('implementation_year')
            ->value('code');
    }

    /**
     * @return list<int>
     */
    public function resolveSyllabusIdsForProgram(StudentApplication $program): array
    {
        return CourseSyllabus::query()
            ->whereHas('departmentLevelCourse', function ($query) use ($program): void {
                $query
                    ->where('department_level_id', $program->department_level_id)
                    ->where('department_course_id', $program->department_course_id);
            })
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @return list<int>
     */
    public function resolveSyllabusIds(StudentEnrolment $enrolment): array
    {
        $fromAssignedClass = array_values(array_map(
            'intval',
            array_filter($enrolment->academicCalendarStudentEnrolment
                ?->academicCalendarClass
                ?->classConfig
                ?->course_syllabus_ids ?? [])
        ));

        if ($fromAssignedClass !== []) {
            return $fromAssignedClass;
        }

        $classConfig = ClassConfig::query()
            ->where('department_level_id', $enrolment->department_level_id)
            ->where('department_course_id', $enrolment->department_course_id)
            ->where('academic_year_option_id', $enrolment->academic_year_option_id)
            ->where('mode_of_study_id', $enrolment->mode_of_study_id)
            ->when(
                $enrolment->academicCalendar?->calendar_year,
                fn ($query, string $calendarYear) => $query->where('calendar_year', $calendarYear),
            )
            ->first();

        if ($classConfig !== null) {
            $fromClassConfig = array_values(array_map(
                'intval',
                array_filter($classConfig->course_syllabus_ids ?? [])
            ));

            if ($fromClassConfig !== []) {
                return $fromClassConfig;
            }
        }

        return CourseSyllabus::query()
            ->whereHas('departmentLevelCourse', function ($query) use ($enrolment): void {
                $query
                    ->where('department_level_id', $enrolment->department_level_id)
                    ->where('department_course_id', $enrolment->department_course_id);
            })
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }
}
