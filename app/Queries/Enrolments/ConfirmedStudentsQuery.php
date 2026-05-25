<?php

namespace App\Queries\Enrolments;

use App\Enums\Shared\ClassListTypeEnum;
use App\Models\Students\StudentProgram;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ConfirmedStudentsQuery
{
    /**
     * Raw FINAL class-list counts per (department_course_id, department_level_id) for a department + mode.
     *
     * @return array<string, int> Keys "{department_course_id}_{department_level_id}"
     */
    public function countsByCourseLevel(int $institutionDepartmentId, int $modeOfStudyId, string $calendarYear): array
    {
        $rows = $this->baseQueryWithFinalClassList($institutionDepartmentId, $modeOfStudyId)
            ->join('intake_periods', function ($join) use ($calendarYear): void {
                $join->on('intake_periods.id', '=', 'student_programs.intake_period_id')
                    ->where('intake_periods.calendar_year', '=', $calendarYear)
                    ->whereNull('intake_periods.deleted_at');
            })
            ->selectRaw('student_programs.department_course_id, student_programs.department_level_id, COUNT(*) as total')
            ->groupBy('student_programs.department_course_id', 'student_programs.department_level_id')
            ->get();

        $lookup = [];

        foreach ($rows as $row) {
            $key = "{$row->department_course_id}_{$row->department_level_id}";
            $lookup[$key] = (int) $row->total;
        }

        return $lookup;
    }

    /**
     * FINAL-list students with a student_enrolment for any of the given academic calendars (needed for class allocation payloads).
     *
     * @param  list<int>  $academicCalendarIds
     */
    public function listForClassAllocation(
        int $institutionDepartmentId,
        int $departmentLevelId,
        int $departmentCourseId,
        int $modeOfStudyId,
        array $academicCalendarIds
    ): Collection {
        if ($academicCalendarIds === []) {
            return collect();
        }

        return $this->baseQueryWithFinalClassList($institutionDepartmentId, $modeOfStudyId)
            ->join('students', 'students.id', '=', 'student_programs.student_id')
            ->join('student_enrolments', function ($join) use ($academicCalendarIds): void {
                $join->on('student_enrolments.student_program_id', '=', 'student_programs.id')
                    ->whereIn('student_enrolments.academic_calendar_id', $academicCalendarIds)
                    ->whereColumn('student_enrolments.mode_of_study_id', 'student_programs.mode_of_study_id')
                    ->whereNull('student_enrolments.deleted_at');
            })
            ->leftJoin('genders', 'genders.id', '=', 'students.gender_id')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->where('student_programs.department_level_id', $departmentLevelId)
            ->where('student_programs.department_course_id', $departmentCourseId)
            ->select([
                'student_programs.id as student_program_id',
                'student_enrolments.id as student_enrolment_id',
                'student_programs.student_id',
                'student_programs.application_tracking_number',
                'genders.title as gender_title',
                'users.first_name',
                'users.middle_name',
                'users.last_name',
            ])
            ->orderBy('users.first_name')
            ->orderBy('users.last_name')
            ->get();
    }

    private function baseQueryWithFinalClassList(int $institutionDepartmentId, int $modeOfStudyId): Builder
    {
        return StudentProgram::query()
            ->join('class_lists', function ($join): void {
                $join->on('class_lists.student_program_id', '=', 'student_programs.id')
                    ->where('class_lists.type', ClassListTypeEnum::FINAL->value)
                    ->whereNull('class_lists.deleted_at');
            })
            ->where('student_programs.institution_department_id', $institutionDepartmentId)
            ->where('student_programs.mode_of_study_id', $modeOfStudyId)
            ->whereNull('student_programs.deleted_at');
    }
}
